<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\profilepage\Entities\Bases;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\profilepage\Entities\Authentication\Event;
use SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob;
use SimpleSAML\Module\profilepage\Entities\Bases\AbstractState;
use SimpleSAML\Module\profilepage\Exceptions\StateException;
use SimpleSAML\Module\profilepage\Helpers\AuthenticationEventStateResolver;
use SimpleSAML\Module\profilepage\Services\HelpersManager;

/**
 * @covers \SimpleSAML\Module\profilepage\Entities\Bases\AbstractJob
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event\Job
 * @uses \SimpleSAML\Module\profilepage\Entities\Authentication\Event
 */
class AbstractJobTest extends TestCase
{
    protected array $rawState = [];

    /**
     * @throws StateException
     */
    public function testCanInitializeProperties(): void
    {
        $id = 1;
        $createdAt = new DateTimeImmutable();
        $helpersManager = $this->createMock(HelpersManager::class);
        $samlState = $this->createMock(Event\State\Saml2::class);
        $authenticationEventStateResolver = $this->createMock(AuthenticationEventStateResolver::class);
        $authenticationEventStateResolver->expects($this->once())->method('fromStateArray')
            ->with($this->isType('array'))->willReturn($samlState);
        $helpersManager->expects($this->once())->method('getAuthenticationEventStateResolver')
            ->willReturn($authenticationEventStateResolver);
        $job = new class ($this->rawState, $id, $createdAt, $helpersManager) extends AbstractJob  {
            public function getType(): string
            {
                return self::class;
            }
        };

        $this->assertSame($id, $job->getId());
        $this->assertIsArray($job->getRawState());
        $this->assertArrayHasKey(AbstractState::KEY_AUTHENTICATION_INSTANT, $job->getRawState());
        $this->assertSame($createdAt, $job->getCreatedAt());
        $this->assertInstanceOf(Event::class, $job->getAuthenticationEvent());
        $this->assertSame($job::class, $job->getType());
    }
}
