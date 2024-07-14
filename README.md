<!-- ABOUT THE PROJECT -->
## About The Project

Component BreezingForms V5 for Joomla 5.0.

## Getting Started

## Migration
The Joomla aliases have been removed to prepare Joomla 6.

| Before      | After     |
| ------------- | ------------- |
| JFactory::getDbo() | Factory::getContainer()->get(DatabaseInterface::class) |
| ->query();     | ->execute(); |
| JFactory::getUser() | Factory::getApplication()->getIdentity() |
| JFactory::getUser($id) | Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id)|
| | Factory::getApplication()->getSession()|


## Installation

    Clone the repo

    git clone https://github.com/vcmb-cyclo/com_breezingforms.git
    
## Download plugin

    Click on tag label.
    Download "Source code (zip)" file.
