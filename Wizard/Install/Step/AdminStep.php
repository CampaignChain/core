<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard\Install\Step;

use FOS\UserBundle\Command\CreateUserCommand;
use Sensio\Bundle\DistributionBundle\Configurator\Step\StepInterface;
use Symfony\Component\Validator\Constraints as Assert;
use CampaignChain\CoreBundle\Wizard\Install\Form\AdminStepType;
use Symfony\Bundle\FrameworkBundle\Console\Application,
    Symfony\Component\Console\Input\ArrayInput,
    Symfony\Component\Console\Output\NullOutput;

class AdminStep implements StepInterface
{
    /**
     * @Assert\NotBlank
     */
    public $first_name;

    /**
     * @Assert\NotBlank
     */
    public $last_name;

    /**
     * @Assert\NotBlank
     */
    public $password;

    /**
     * @Assert\NotBlank
     * @Assert\Email
     */
    public $email;

    public $support;

    private $context;

    private $kernel;
    private $templating;
    private $mailer;
    private $em;

    public function setKernel($kernel){
        $this->kernel = $kernel;
    }

    public function setTemplating($templating){
        $this->templating = $templating;
    }

    public function setMailer($mailer){
        $this->mailer = $mailer;
    }

    public function setEntityManager($em){
        $this->em = $em;
    }

    public function setContext(array $context){
        $this->context = $context;
    }

    public function setParameters(array $parameters)
    {
        if(!isset($parameters['first_name'])){
            $this->first_name = null;
        } else {
            $this->first_name = $parameters['first_name'];
        }
        if(!isset($parameters['last_name'])){
            $this->last_name = null;
        } else {
            $this->last_name = $parameters['last_name'];
        }
        if(!isset($parameters['first_name'])){
            $this->password = null;
        } else {
            $this->password = $parameters['password'];
        }

        if(!isset($parameters['email'])){
            $this->email = null;
        } else {
            $this->email = $parameters['email'];
        }
    }

    /**
     * @see StepInterface
     */
    public function getFormType()
    {
        return new AdminStepType();
    }

    /**
     * @see StepInterface
     */
    public function checkRequirements()
    {
        return array();
    }

    /**
     * checkOptionalSettings
     */
    public function checkOptionalSettings()
    {
        return array();
    }

    /**
     * @see StepInterface
     */
    public function update(StepInterface $data)
    {
        return array(
            'first_name' => $data->first_name,
            'last_name' => $data->last_name,
            'email' => $data->email,
            'password' => $data->password,
            'support' => $data->support,
        );
    }

    /**
     * @see StepInterface
     */
    public function getTemplate()
    {
        return 'CampaignChainCoreBundle:Wizard/Install/Step:admin.html.twig';
    }

    public function execute($parameters)
    {
        // If admin user is already in DB, then delete it.
        $admin = $this->em->getRepository('CampaignChainCoreBundle:User')->findOneByUsername('admin');
        if($admin){
            $this->em->remove($admin);
            $this->em->flush();
        }

        // Create admin user in database.
        $application = new Application($this->kernel);
        $application->add(new CreateUserCommand());
        $command = $application->find('fos:user:create');

        $arguments = array(
            'doctrine:schema:update',
            'username' => 'admin',
            '--super-admin' => true,
            'email' => $this->email,
            'password' => $this->password,
        );
        $input = new ArrayInput($arguments);
        $output = new NullOutput();

        $command->run($input, $output);

        if($this->support){
            $message = \Swift_Message::newInstance()
                ->setSubject('30 Days Free Support for '.$this->first_name.' '.$this->last_name)
                ->setFrom($this->email)
                ->setTo('support@campaignchain.com')
                ->setBody(
                    $this->templating->renderResponse(
                        'CampaignChainCoreBundle:Wizard/Install/Step:admin_email.html.twig',
                        array(
                            'first_name' => $this->first_name,
                            'last_name' => $this->last_name,
                            'email' => $this->email,
                        )
                    )
                )
            ;
            $this->mailer->send($message);
        }
    }
}
