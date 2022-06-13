<?php

namespace SimpleSAML\Module\accounting\Controller;

use SimpleSAML\Configuration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

class Test
{
    protected Configuration $configuration;
    protected Session $session;

    /**
     * @param Configuration $configuration
     * @param Session $session The current user session.
     */
    public function __construct(Configuration $configuration, Session $session)
    {
        $this->configuration = $configuration;
        $this->session = $session;
    }

    /**
     * @param Request $request
     * @return Template
     * @throws \Exception
     */
    public function test(Request $request): Template
    {
        $template = new Template(Configuration::getConfig(), 'accounting:configuration.twig');

        $template->data = [
            'test' => Translate::noop('Accounting'),
        ];

        return $template;
    }
}
