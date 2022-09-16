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

    public function testCanResolveIdpEntityId(): void
    {
        $stateArray = StateArrays::FULL;
        $state = new State($stateArray);
        $this->assertSame($state->getIdpEntityId(), StateArrays::FULL['IdPMetadata']['entityid']);

        $this->expectException(UnexpectedValueException::class);
        unset($stateArray['IdPMetadata']['entityid']);
        new State($stateArray);
    }

    public function testCanResolveSpEntityId(): void
    {
        $stateArray = StateArrays::FULL;
        $state = new State($stateArray);
        $this->assertSame($state->getSpEntityId(), StateArrays::FULL['SPMetadata']['entityid']);

        $this->expectException(UnexpectedValueException::class);
        unset($stateArray['SPMetadata']['entityid']);
        new State($stateArray);
    }

    public function testCanResolveAttributes(): void
    {
        $state = new State(StateArrays::FULL);
        $this->assertSame($state->getAttributes(), StateArrays::FULL['Attributes']);
    }

    public function testUseCurrentDateTimeIfAuthnInstantNotPresent(): void
    {
        $stateArray = StateArrays::FULL;

        unset($stateArray['AuthnInstant']);

        $state = new State($stateArray);

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

    public function testCanResolveIdpMetadataArray(): void
    {
        // Metadata from 'IdPMetadata'
        $sampleState = StateArrays::FULL;
        $state = new State($sampleState);
        $this->assertEquals($sampleState['IdPMetadata'], $state->getIdpMetadataArray());

        // Fallback metadata from 'Source'
        unset($sampleState['IdPMetadata']);
        $state = new State($sampleState);
        $this->assertEquals($sampleState['Source'], $state->getIdpMetadataArray());

        // Throws on no IdP metadata
        $this->expectException(UnexpectedValueException::class);
        unset($sampleState['Source']);
        new State($sampleState);
    }

    public function testCanResolveSpMetadataArray(): void
    {
        // Metadata from 'IdPMetadata'
        $sampleState = StateArrays::FULL;
        $state = new State($sampleState);
        $this->assertEquals($sampleState['SPMetadata'], $state->getSpMetadataArray());

        // Fallback metadata from 'Destination'
        unset($sampleState['SPMetadata']);
        $state = new State($sampleState);
        $this->assertEquals($sampleState['Destination'], $state->getSpMetadataArray());

        // Throws on no SP metadata
        $this->expectException(UnexpectedValueException::class);
        unset($sampleState['Destination']);
        new State($sampleState);
    }

    public function testCanGetCreatedAt(): void
    {
        $state = new State(StateArrays::FULL);
        $this->assertInstanceOf(\DateTimeImmutable::class, $state->getCreatedAt());
    }
}
