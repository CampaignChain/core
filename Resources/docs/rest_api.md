# Campaigns #

### `POST` /api/v1/p/campaignchain/campaign-scheduled/campaigns ###

_Create new Scheduled Campaign_



# Core #

### `GET` /api/private/activities/{id} ###

_Get one specific Activity._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of an Activity, e.g. '42'.


### `GET` /api/private/campaigns ###

_Get a list of Campaigns._

#### Filters ####

fromNow:

  * Requirement: (done|ongoing|upcoming)[]
  * Description: Filters per start and end date, options: 'upcoming' (start date is after now), 'ongoing' (start date is before and end date after now), 'done' (end date is before now).

status:

  * Requirement: (open|closed|paused|background process|interaction required)[]
  * Description: Workflow status of a campaign.

moduleUri:

  * Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\\/[A-Za-z0-9][A-Za-z0-9_.-]*\\/[A-Za-z0-9][A-Za-z0-9_.-]*[]
  * Description: The module URI of a campaign module, e.g. campaignchain\/campaign-scheduled\/campaignchain-scheduled.


### `GET` /api/private/channels ###

_Get a list of all installed Channels._


### `GET` /api/private/channels/locations/urls ###

_List all URLs of all connected Locations in all Channels._


### `GET` /api/private/channels/locations/urls/{url} ###

_Get a specific Channel Location by its URL._

#### Requirements ####

**url**

  - Requirement: .+
  - Type: string
  - Description: URL of a Channel connected to CampaignChain, e.g. 'https://twitter.com/AmarikiTest1'.


### `GET` /api/private/locations/locations/{id} ###

_Get a specific Location by its ID._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: Location ID


### `POST` /api/private/locations/toggle-status ###

_Toggle the status of a Location to active or inactive._

#### Requirements ####

**id**

  - Requirement: \d+
  - Description: Location ID


### `GET` /api/private/modules ###

_Get a list of all installed CampaignChain modules._


### `GET` /api/private/modules/packages ###

_List all installed Composer packages which include CampaignChain Modules._


### `GET` /api/private/modules/packages/{package} ###

_Get all modules contained in a Composer package._

#### Requirements ####

**package**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*
  - Type: string
  - Description: A Composer package's name, e.g. 'campaignchain/location-facebook'. The value should be URL encoded.


### `POST` /api/private/modules/toggle-status ###

_Toggle the status of a Module to active or inactive._

#### Requirements ####

**uri**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*


### `GET` /api/private/modules/types ###

_List all available types for modules_


### `GET` /api/private/modules/types/{type} ###

_Get all modules of same type._

#### Requirements ####

**type**

  - Requirement: (campaign|channel|location|activity|operation|report|security)
  - Type: string
  - Description: The type of a module, e.g. 'location'.


### `GET` /api/private/modules/uris ###

_List all available Module URIs._


### `GET` /api/private/modules/uris/{uri} ###

_Get one specific module by Module URI._

#### Requirements ####

**uri**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*
  - Type: string
  - Description: A Module URI, e.g. 'campaignchain/location-facebook/campaignchain-facebook-user'. The value should be URL encoded.


### `GET` /api/private/packages ###

_Get a list of all installed Composer packages containing CampaignChain modules._


### `GET` /api/private/packages/vendors ###

_List all available vendors of installed Composer packages containing CampaignChain Modules._


### `GET` /api/private/packages/vendors/{vendor} ###

_Get all Composer packages of a vendor that contain modules._

#### Requirements ####

**vendor**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*
  - Type: string
  - Description: A Composer package's vendor name, e.g. 'campaignchain'.


### `GET` /api/private/packages/{package} ###

_Get one specific Composer package that contains CampaignChain modules._

#### Requirements ####

**package**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*
  - Type: string
  - Description: A Composer package's name, e.g. 'campaignchain/location-facebook'. The value should be URL encoded.


### `GET` /api/private/routes ###

_Get a list of system URLs, so-called routes._


### `GET` /api/private/users ###

_Get a list of all users._


### `GET` /api/private/users/{id} ###

_Get one specific user._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of a user, e.g. '42'.


### `GET` /api/v1/activities/{id} ###

_Get one specific Activity._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of an Activity, e.g. '42'.


### `GET` /api/v1/campaigns ###

_Get a list of Campaigns._

#### Filters ####

fromNow:

  * Requirement: (done|ongoing|upcoming)[]
  * Description: Filters per start and end date, options: 'upcoming' (start date is after now), 'ongoing' (start date is before and end date after now), 'done' (end date is before now).

status:

  * Requirement: (open|closed|paused|background process|interaction required)[]
  * Description: Workflow status of a campaign.

moduleUri:

  * Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\\/[A-Za-z0-9][A-Za-z0-9_.-]*\\/[A-Za-z0-9][A-Za-z0-9_.-]*[]
  * Description: The module URI of a campaign module, e.g. campaignchain\/campaign-scheduled\/campaignchain-scheduled.


### `GET` /api/v1/channels ###

_Get a list of all installed Channels._


### `GET` /api/v1/channels/locations/urls ###

_List all URLs of all connected Locations in all Channels._


### `GET` /api/v1/channels/locations/urls/{url} ###

_Get a specific Channel Location by its URL._

#### Requirements ####

**url**

  - Requirement: .+
  - Type: string
  - Description: URL of a Channel connected to CampaignChain, e.g. 'https://twitter.com/AmarikiTest1'.


### `GET` /api/v1/locations/locations/{id} ###

_Get a specific Location by its ID._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: Location ID


### `POST` /api/v1/locations/toggle-status ###

_Toggle the status of a Location to active or inactive._

#### Requirements ####

**id**

  - Requirement: \d+
  - Description: Location ID


### `GET` /api/v1/modules ###

_Get a list of all installed CampaignChain modules._


### `GET` /api/v1/modules/packages ###

_List all installed Composer packages which include CampaignChain Modules._


### `GET` /api/v1/modules/packages/{package} ###

_Get all modules contained in a Composer package._

#### Requirements ####

**package**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*
  - Type: string
  - Description: A Composer package's name, e.g. 'campaignchain/location-facebook'. The value should be URL encoded.


### `POST` /api/v1/modules/toggle-status ###

_Toggle the status of a Module to active or inactive._

#### Requirements ####

**uri**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*


### `GET` /api/v1/modules/types ###

_List all available types for modules_


### `GET` /api/v1/modules/types/{type} ###

_Get all modules of same type._

#### Requirements ####

**type**

  - Requirement: (campaign|channel|location|activity|operation|report|security)
  - Type: string
  - Description: The type of a module, e.g. 'location'.


### `GET` /api/v1/modules/uris ###

_List all available Module URIs._


### `GET` /api/v1/modules/uris/{uri} ###

_Get one specific module by Module URI._

#### Requirements ####

**uri**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*
  - Type: string
  - Description: A Module URI, e.g. 'campaignchain/location-facebook/campaignchain-facebook-user'. The value should be URL encoded.


### `GET` /api/v1/packages ###

_Get a list of all installed Composer packages containing CampaignChain modules._


### `GET` /api/v1/packages/vendors ###

_List all available vendors of installed Composer packages containing CampaignChain Modules._


### `GET` /api/v1/packages/vendors/{vendor} ###

_Get all Composer packages of a vendor that contain modules._

#### Requirements ####

**vendor**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*
  - Type: string
  - Description: A Composer package's vendor name, e.g. 'campaignchain'.


### `GET` /api/v1/packages/{package} ###

_Get one specific Composer package that contains CampaignChain modules._

#### Requirements ####

**package**

  - Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*
  - Type: string
  - Description: A Composer package's name, e.g. 'campaignchain/location-facebook'. The value should be URL encoded.


### `GET` /api/v1/routes ###

_Get a list of system URLs, so-called routes._


### `GET` /api/v1/users ###

_Get a list of all users._


### `GET` /api/v1/users/{id} ###

_Get one specific user._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of a user, e.g. '42'.



# Packages: Event Stream Processing (ESP) #

### `POST` /api/private/esp/event ###

_Send event data to CampaignChain to track any actions a user performs,_


### `POST` /api/v1/esp/event ###

_Send event data to CampaignChain to track any actions a user performs,_



# Packages: Facebook #

### `POST` /api/private/p/campaignchain/activity-facebook/statuses ###

_Schedule a Facebook status_


### `GET` /api/private/p/campaignchain/activity-facebook/statuses/{id} ###

_Get a specific Facebook status._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of an Activity, e.g. '42'.


### `POST` /api/v1/p/campaignchain/activity-facebook/statuses ###

_Schedule a Facebook status_


### `GET` /api/v1/p/campaignchain/activity-facebook/statuses/{id} ###

_Get a specific Facebook status._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of an Activity, e.g. '42'.



# Packages: Twitter #

### `POST` /api/private/p/campaignchain/activity-twitter/statuses ###

_Schedule a Twitter status_


### `GET` /api/private/p/campaignchain/activity-twitter/statuses/{id} ###

_Get a specific Twitter status._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of an Activity, e.g. '42'.


### `GET` /api/private/p/campaignchain/channel-twitter/users/search ###

_Search for users on Twitter._

#### Filters ####

q:

  * Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*
  * Description: The search query to run against people search.

location:

  * Requirement: \d+
  * Description: The ID of a CampaignChain Location you'd like to use to connect with Twitter.


### `POST` /api/v1/p/campaignchain/activity-twitter/statuses ###

_Schedule a Twitter status_


### `GET` /api/v1/p/campaignchain/activity-twitter/statuses/{id} ###

_Get a specific Twitter status._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of an Activity, e.g. '42'.


### `GET` /api/v1/p/campaignchain/channel-twitter/users/search ###

_Search for users on Twitter._

#### Filters ####

q:

  * Requirement: [A-Za-z0-9][A-Za-z0-9_.-]*
  * Description: The search query to run against people search.

location:

  * Requirement: \d+
  * Description: The ID of a CampaignChain Location you'd like to use to connect with Twitter.



# Packages: eZ Platform #

### `POST` /api/v1/p/campaignchain/activity-ezplatform/objects ###

_Schedule an eZ Platform content object._


### `GET` /api/v1/p/campaignchain/activity-ezplatform/objects/{id} ###

_Get a specific eZ Platform object._

#### Requirements ####

**id**

  - Requirement: \d+
  - Type: string
  - Description: The ID of an Activity, e.g. '42'.


### `GET` /api/v1/p/ccampaignchain/location-ezplatform/objects ###

_Get a list of eZ Platform content object Locations._
