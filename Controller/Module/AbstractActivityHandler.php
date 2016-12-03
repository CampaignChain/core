<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\CoreBundle\Controller\Module;

use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Operation;
use Symfony\Component\Form\Form;

/**
 * Class AbstractActivityHandler
 * @package CampaignChain\CoreBundle\Controller\Module
 */
abstract class AbstractActivityHandler
{
    /**
     * When a new Activity is being created, this handler method will be called
     * to retrieve a new Content object for the Activity.
     *
     * Called in these views:
     * - new
     *
     * @param Location $location
     * @param Campaign $campaign
     * @return null
     */
    public function createContent(Location $location = null, Campaign $campaign = null)
    {
        return null;
    }

    /**
     * Overwrite this method to return an existing Activity Content object which
     * would be displayed in a view.
     *
     * Called in these views:
     * - edit
     * - editModal
     * - read
     *
     * @param Location $location
     * @param Operation $operation
     * @return null
     */
    public function getContent(Location $location, Operation $operation)
    {
        return null;
    }

    /**
     * Implement this method to change the data of an Activity as per the form
     * data that has been posted in a view.
     *
     * Called in these views:
     * - new
     *
     * @param Activity $activity
     * @param $data Form submit data of the Activity.
     * @return Activity
     */
    public function processActivity(Activity $activity, $data)
    {
        return $activity;
    }

    /**
     * Modifies the Location of the Activity.
     *
     * Called in these views:
     * - new
     *
     * @param Location $location The Activity's Location.
     * @return Location
     */
    public function processActivityLocation(Location $location)
    {
        return $location;
    }

    /**
     * After a new Activity was created, this method makes it possible to alter
     * the data of the Content's Location (not the Activity's Location!) as per
     * the data provided for the Content.
     *
     * Called in these views:
     * - new
     *
     * @param Location $location Location of the Content.
     * @param $data Form submit data of the Content.
     * @return Location
     */
    public function processContentLocation(Location $location, $data)
    {
        return $location;
    }

    /**
     * Create or modify the Content object from the form data.
     *
     * Called in these views:
     * - new
     * - editApi
     *
     * @param Operation $operation
     * @param $data Form submit data of the Content. Either an array if a new Activity, or the respective Content object as defined by the Module.
     * @return mixed
     */
    abstract public function processContent(Operation $operation, $data);

    /**
     * Define custom template rendering options for the new view in this method
     * as an array. Here's a sample of such an array:
     *
     * array(
     *     'template' => 'foo_template::edit.html.twig',
     *     'vars' => array(
     *         'foo1' => $bar1,
     *         'foo2' => $bar2
     *         )
     *     );
     *
     * Called in these views:
     * - new
     *
     * @return null
     */
    public function getNewRenderOptions()
    {
        return null;
    }

    /**
     * Overwrite this method to define how the Content is supposed to be
     * displayed.
     *
     * Called in these views:
     * - read
     *
     * @param Operation $operation
     * @return mixed
     */
    abstract public function readAction(Operation $operation);

    /**
     * The Activity controller calls this method after the form was submitted
     * and the new activity was persisted.
     *
     * @param Activity $activity
     * @param $data
     */
    public function postFormSubmitNewEvent(Activity $activity, $data)
    {
    }

    /**
     * This event is being called after the new Activity and its related
     * content was persisted.
     *
     * Called in these views:
     * - new
     *
     * @param Operation $operation
     * @param $content The Activity's content object.
     * @return null
     */
    public function postPersistNewEvent(Operation $operation, $content = null)
    {
        return null;
    }

    /**
     * This event is being called before the edit form data has been submitted.
     *
     * Called in these views:
     * - edit
     *
     * @param Operation $operation
     * @return null
     */
    public function preFormSubmitEditEvent(Operation $operation)
    {
        return null;
    }

    /**
     * This event is being called after the edited Activity and its related
     * content was persisted.
     *
     * Called in these views:
     * - edit
     *
     * @param Operation $operation
     * @param $content The Activity's content object.
     * @return null
     */
    public function postPersistEditEvent(Operation $operation, $content = null)
    {
        return null;
    }

    /**
     * Define custom template rendering options for the edit view in this method
     * as an array. Here's a sample of such an array:
     *
     * array(
     *     'template' => 'foo_template::edit.html.twig',
     *     'vars' => array(
     *         'foo1' => $bar1,
     *         'foo2' => $bar2
     *         )
     *     );
     *
     * Called in these views:
     * - edit
     *
     * @param Operation $operation
     * @return null
     */
    public function getEditRenderOptions(Operation $operation)
    {
        return null;
    }

    /**
     * This event is being called before the editModal form data has been
     * submitted.
     *
     * Called in these views:
     * - editModal
     *
     * @param Operation $operation
     * @return null
     */
    public function preFormSubmitEditModalEvent(Operation $operation)
    {
        return null;
    }

    /**
     * Define custom template rendering options for editModal view as array.
     *
     * Called in these views:
     * - editModal
     *
     * @see AbstractActivityHandler::getEditRenderOptions()
     * @param Operation $operation
     * @return null
     */
    public function getEditModalRenderOptions(Operation $operation)
    {
        return null;
    }

    /**
     * Let's a handler implementation define whether the Content should be
     * displayed or processed in a specific view or not.
     *
     * Called in these views:
     * - new
     * - edit
     * - editModal
     * - editApi
     *
     * @param $view
     * @return bool
     */
    public function hasContent($view)
    {
        return true;
    }
}