<?php

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Controller;

use SimpleSAML\Configuration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

class Test
{
    protected Configuration $configuration;
    protected Session $session;
    protected ModuleConfiguration $moduleConfiguration;

    /**
     * @param Configuration $configuration
     * @param Session $session The current user session.
     * @param ModuleConfiguration $moduleConfiguration
     */
    public function __construct(
        Configuration $configuration,
        Session $session,
        ModuleConfiguration $moduleConfiguration
    ) {
        $this->configuration = $configuration;
        $this->session = $session;
        $this->moduleConfiguration = $moduleConfiguration;
    }

    /**
     * @param Request $request
     * @return Template
     * @throws \Exception
     */
    public function test(Request $request): Template
    {
        $template = new Template($this->configuration, 'accounting:configuration.twig');

        $template->data = [
            'test' => Translate::noop('Accounting'),
            'test_config' => $this->moduleConfiguration->getConfiguration()->getString('test_config'),
        ];

        return $template;
    }
}
