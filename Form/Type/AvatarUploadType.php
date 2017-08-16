<?php


namespace CampaignChain\CoreBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvatarUploadType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'avatar_upload';
    }

    public function getParent()
    {
        return TextType::class;
    }
}