<?php

namespace SimpleSAML\Test\Module\accounting\Entities\Authentication;

use SimpleSAML\Module\accounting\Entities\Authentication\State;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\accounting\Exceptions\UnexpectedValueException;
use SimpleSAML\Test\Module\accounting\Constants\StateArrays;

/**
 * @covers \SimpleSAML\Module\accounting\Entities\Authentication\State
 */
class StateTest extends TestCase
{
    public function testCanInitializeValidState(): void
    {
        $state = new State(StateArrays::FULL);

        $this->assertSame($state->getIdpEntityId(), StateArrays::FULL['Source']['entityid']);
    }

    public function testUseIdPMetadataForEntityIdIfSourceNotAvailable(): void
    {
        $stateArray = StateArrays::FULL;

        unset($stateArray['Source']);

        $state = new State($stateArray);

        $this->assertSame($state->getIdpEntityId(), StateArrays::FULL['Source']['entityid']);
        $this->assertSame($state->getSpEntityId(), StateArrays::FULL['Destination']['entityid']);
        $this->assertSame($state->getAttributes(), StateArrays::FULL['Attributes']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $state->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $state->getAuthnInstant());
    }

    public function testThrowsOnMissingSourceEntityId(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::FULL;

        unset($stateArray['Source'], $stateArray['IdPMetadata']);

        /** @psalm-suppress UnusedMethodCall */
        (new State($stateArray));
    }

    public function testUseSpMetadataForEntityIdIfDestinationNotAvailable(): void
    {
        $stateArray = StateArrays::FULL;

        unset($stateArray['Destination']);

        $state = new State($stateArray);

        $this->assertSame($state->getSpEntityId(), StateArrays::FULL['SPMetadata']['entityid']);
    }

    public function testThrowsOnMissingDestinationEntityId(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::FULL;

        unset($stateArray['Destination'], $stateArray['SPMetadata']);

        (new State($stateArray));
    }

    public function testThrowsOnMissingAuthnInstant(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::FULL;
        unset($stateArray['AuthnInstant']);

        new State($stateArray);
    }

    public function testThrowsOnInvalidAuthnInstantValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::FULL;
        $stateArray['AuthnInstant'] = 'invalid';

        new State($stateArray);
    }

    public function testThrowsOnMissingAttributes(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $stateArray = StateArrays::FULL;

        unset($stateArray['Attributes']);

        /** @psalm-suppress UnusedMethodCall */
        (new State($stateArray));
    }

    public function testCanGetAttributeValue(): void
    {
        $state = new State(StateArrays::FULL);

        $this->assertSame(
            StateArrays::FULL['Attributes']['hrEduPersonUniqueID'][0],
            $state->getAttributeValue('hrEduPersonUniqueID')
        );

        $this->assertNull($state->getAttributeValue('non-existent'));
    }
}
