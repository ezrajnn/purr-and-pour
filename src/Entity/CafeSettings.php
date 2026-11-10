<?php

namespace App\Entity;

use App\Repository\CafeSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CafeSettingsRepository::class)]
#[ORM\Table(name: 'cafe_settings')]
class CafeSettings
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private ?string $settingKey = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $settingValue = null;

    public function getSettingKey(): ?string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): static
    {
        $this->settingKey = $settingKey;

        return $this;
    }

    public function getSettingValue(): ?string
    {
        return $this->settingValue;
    }

    public function setSettingValue(string $settingValue): static
    {
        $this->settingValue = $settingValue;

        return $this;
    }
}