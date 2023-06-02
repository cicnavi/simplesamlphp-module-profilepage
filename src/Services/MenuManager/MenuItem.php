<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Services\MenuManager;

class MenuItem
{
    protected string $hrefPath;
    protected string $label;
    protected ?string $iconAssetPath;

    public function __construct(
        string $hrefPath,
        string $label,
        string $iconAssetPath = null
    ) {
        $this->hrefPath = $hrefPath;
        $this->label = $label;
        $this->iconAssetPath = $iconAssetPath;
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

    /**
     * @return string
     */
    public function getIconAssetPath(): ?string
    {
        return $this->iconAssetPath;
    }
}
