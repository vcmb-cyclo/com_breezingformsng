<?php

declare(strict_types=1);

namespace Joomla\CMS\MVC\Model;

if (!class_exists(ListModel::class)) {
    class ListModel
    {
        /** @var array<string, mixed> */
        private array $stubState = [];

        public function getState($property = null, $default = null)
        {
            return $this->stubState[$property] ?? $default;
        }

        public function setState($property, $value = null)
        {
            $this->stubState[$property] = $value;
        }
    }
}
