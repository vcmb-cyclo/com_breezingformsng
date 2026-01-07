<!-- ABOUT THE PROJECT -->
## About The Project

Component BreezingForms for Joomla 6.

## Getting Started

## Migration
The Joomla aliases have been removed to prepare Joomla 6.

| Before      | After     |
| ------------- | ------------- |
| JFactory::getDbo() | Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class) |
| ->query();     | ->execute(); |
| JFactory::getUser() | Joomla\CMS\Factory::getApplication()->getIdentity() |
| JFactory::getUser($id) | Joomla\CMS\Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id)|
| | Factory::getApplication()->getSession()|
| See more | https://manual.joomla.org/migrations/44-50/compat-plugin/ |


## Installation

    Clone the repo

    git clone https://github.com/vcmb-cyclo/com_breezingforms.git
    
## Download plugin

    Click on tag label.
    Download "Source code (zip)" file.
