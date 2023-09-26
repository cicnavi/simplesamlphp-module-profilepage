<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services\MenuManager;

class MenuItem
{
    public function __construct(
        protected string $hrefPath,
        protected string $label,
        protected ?string $iconAssetPath = null
    ) {
    }

    /**
     * @return string
     */
    public function getHrefPath(): string
    {
        return $this->hrefPath;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIconAssetPath(): ?string
    {
        return $this->iconAssetPath;
    }
}
