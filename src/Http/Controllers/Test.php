<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace SimpleSAML\Module\accounting\Http\Controllers;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration as SspConfiguration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\accounting\Entities\Authentication\Event;
use SimpleSAML\Module\accounting\Entities\Authentication\State;
use SimpleSAML\Module\accounting\ModuleConfiguration;
use SimpleSAML\Module\accounting\Stores\Builders\JobsStoreBuilder;
use SimpleSAML\Module\accounting\Stores\Data\Authentication\DoctrineDbal\Versioned\Store;
use SimpleSAML\Module\accounting\Trackers\Authentication\DoctrineDbal\Versioned\Tracker;
use SimpleSAML\Module\accounting\Trackers\Builders\AuthenticationDataTrackerBuilder;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * TODO mivanci delete this file
 */
class Test
{
    protected SspConfiguration $sspConfiguration;
    protected Session $session;
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;

    /**
     * @param SspConfiguration $sspConfiguration
     * @param Session $session The current user session.
     * @param ModuleConfiguration $moduleConfiguration
     * @param LoggerInterface $logger
     */
    public function __construct(
        SspConfiguration $sspConfiguration,
        Session $session,
        ModuleConfiguration $moduleConfiguration,
        LoggerInterface $logger
    ) {
        $this->sspConfiguration = $sspConfiguration;
        $this->session = $session;
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @return Template
     * @throws Exception
     */
    public function setup(Request $request): Template
    {
        $template = new Template($this->sspConfiguration, 'accounting:test.twig');

        $jobsStore = (new JobsStoreBuilder($this->moduleConfiguration, $this->logger))
            ->build($this->moduleConfiguration->getJobsStoreClass());

        // TODO refactor to tracker builder
        $dataStoreConnectionKey = $this->moduleConfiguration->getClassConnectionKey(Tracker::class);
        $dataStore = Store::build($this->moduleConfiguration, $this->logger, $dataStoreConnectionKey);

        $jobsStoreNeedsSetup = $jobsStore->needsSetup();
        $jobsStoreSetupRan = false;

        $dataStoreNeedsSetup = $dataStore->needsSetup();
        $dataStoreSetupRan = false;


        if ($jobsStoreNeedsSetup && $request->query->has('setup')) {
            $this->logger->info('Jobs Store setup ran.');
            $jobsStore->runSetup();
            $jobsStoreSetupRan = true;
        }

        if ($dataStoreNeedsSetup && $request->query->has('setup')) {
            $this->logger->info('Data Store setup ran.');
            $dataStore->runSetup();
            $dataStoreSetupRan = true;
        }

        $sampleStateArray = ['Responder'=>[0=>'\\SimpleSAML\\Module\\saml\\IdP\\SAML2',1=>'sendResponse',],'\\SimpleSAML\\Auth\\State.exceptionFunc'=>[0=>'\\SimpleSAML\\Module\\saml\\IdP\\SAML2',1=>'handleAuthError',],'\\SimpleSAML\\Auth\\State.restartURL'=>'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/SSOService.php?spentityid=https%3A%2F%2Fpc-example.org.hr%3A9074%2Fsimplesamlphp%2Fsimplesamlphp-2-beta-git%2Fmodule.php%2Fsaml%2Fsp%2Fmetadata.php%2Fdefault-sp&RelayState=https%3A%2F%2Flocalhost.someone.from.hr%3A9074%2Fsimplesamlphp%2Fsimplesamlphp-2-beta-git%2Fmodule.php%2Fadmin%2Ftest%2Fdefault-sp&cookieTime=1660912195','SPMetadata'=>['SingleLogoutService'=>[0=>['Binding'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect','Location'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/singleLogoutService/default-sp',],],'AssertionConsumerService'=>[0=>['Binding'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST','Location'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp','index'=>0,],1=>['Binding'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact','Location'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp','index'=>1,],],'contacts'=>[0=>['emailAddress'=>'example@org.hr','givenName'=>'MarkoIvančić','contactType'=>'technical',],],'entityid'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp','metadata-index'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp','metadata-set'=>'saml20-sp-remote',],'saml:RelayState'=>'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/admin/test/default-sp','saml:RequestId'=>null,'saml:IDPList'=>[],'saml:ProxyCount'=>null,'saml:RequesterID'=>null,'ForceAuthn'=>false,'isPassive'=>false,'saml:ConsumerURL'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp','saml:Binding'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST','saml:NameIDFormat'=>null,'saml:AllowCreate'=>true,'saml:Extensions'=>null,'saml:AuthnRequestReceivedAt'=>1660912195.505402,'saml:RequestedAuthnContext'=>null,'core:IdP'=>'saml2:https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php','core:SP'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp','IdPMetadata'=>['host'=>'localhost.someone.from.hr','privatekey'=>'key.pem','certificate'=>'cert.pem','auth'=>'example-userpass','attributes.NameFormat'=>'urn:oasis:names:tc:SAML:2.0:attrname-format:uri','authproc'=>[100=>['class'=>'core:AttributeMap',0=>'name2oid',],],'entityid'=>'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php','metadata-index'=>'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php','metadata-set'=>'saml20-idp-hosted',],'ReturnCallback'=>[0=>'\\SimpleSAML\\IdP',1=>'postAuth',],'Attributes'=>['hrEduPersonUniqueID'=>[0=>'testuser@primjer2.hr',],'urn:oid:0.9.2342.19200300.100.1.1'=>[0=>'testuser',],'urn:oid:2.5.4.3'=>[0=>'TestNameTestSurname',],'urn:oid:2.5.4.4'=>[0=>'TestSurname',1=>'TestSurname2',],'urn:oid:2.5.4.42'=>[0=>'TestName',],'urn:oid:0.9.2342.19200300.100.1.3'=>[0=>'testusermail@primjer.hr',1=>'testusermail2@primjer.hr',],'urn:oid:2.5.4.20'=>[0=>'123',],'hrEduPersonExtensionNumber'=>[0=>'123',],'urn:oid:0.9.2342.19200300.100.1.41'=>[0=>'123',],'urn:oid:2.5.4.23'=>[0=>'123',],'hrEduPersonUniqueNumber'=>[0=>'LOCAL_NO:100',1=>'OIB:00861590360',],'hrEduPersonOIB'=>[0=>'00861590360',],'hrEduPersonDateOfBirth'=>[0=>'20200101',],'hrEduPersonGender'=>[0=>'9',],'urn:oid:0.9.2342.19200300.100.1.60'=>[0=>'987654321',],'urn:oid:2.5.4.36'=>[0=>'cert-value',],'urn:oid:1.3.6.1.4.1.250.1.57'=>[0=>'http://www.org.hr/~ivekHomePage',],'hrEduPersonProfessionalStatus'=>[0=>'VSS',],'hrEduPersonAcademicStatus'=>[0=>'redovitiprofesor',],'hrEduPersonScienceArea'=>[0=>'sociologija',],'hrEduPersonAffiliation'=>[0=>'djelatnik',1=>'student',],'hrEduPersonPrimaryAffiliation'=>[0=>'djelatnik',],'hrEduPersonStudentCategory'=>[0=>'redovitistudent:diplomskisveučilišnistudij',],'hrEduPersonExpireDate'=>[0=>'20211231',],'hrEduPersonTitle'=>[0=>'rektor',],'hrEduPersonRole'=>[0=>'ICTkoordinator',1=>'ISVUkoordinator',],'hrEduPersonStaffCategory'=>[0=>'nastavnoosoblje',1=>'istraživači',],'hrEduPersonGroupMember'=>[0=>'grupa1',1=>'grupa2',],'urn:oid:2.5.4.10'=>[0=>'Testnaustanova',],'hrEduPersonHomeOrg'=>[0=>'primjer.hr',],'urn:oid:2.5.4.11'=>[0=>'Testnaorgjedinica',],'urn:oid:0.9.2342.19200300.100.1.6'=>[0=>'123',],'urn:oid:2.5.4.16'=>[0=>'Testnaustanova,Baštijanova52A,HR-10000Zagreb',],'urn:oid:2.5.4.7'=>[0=>'Zagreb',],'urn:oid:2.5.4.17'=>[0=>'HR-10000',],'urn:oid:2.5.4.9'=>[0=>'Baštijanova52A',],'urn:oid:0.9.2342.19200300.100.1.39'=>[0=>'Testnaadresa52A,HR-10000,Zagreb',],'urn:oid:0.9.2342.19200300.100.1.20'=>[0=>'123',],'hrEduPersonCommURI'=>[0=>'123',],'hrEduPersonPrivacy'=>[0=>'street',1=>'postalCode',],'hrEduPersonPersistentID'=>[0=>'da4294fb4e5746d57ab6ad88d2daf275',],'urn:oid:2.16.840.1.113730.3.1.241'=>[0=>'testname123',],'urn:oid:1.3.6.1.4.1.25178.1.2.12'=>[0=>'skype:pepe.perez',],'hrEduPersonCardNum'=>[0=>'123',],'updatedAt'=>[0=>'123456789',],'urn:oid:1.3.6.1.4.1.5923.1.1.1.7'=>[0=>'urn:example:oidc:manage:client',],],'Authority'=>'example-userpass','AuthnInstant'=>1660911943,'Expire'=>1660940743,'ReturnCall'=>[0=>'\\SimpleSAML\\IdP',1=>'postAuthProc',],'Destination'=>['SingleLogoutService'=>[0=>['Binding'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect','Location'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/singleLogoutService/default-sp',],],'AssertionConsumerService'=>[0=>['Binding'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST','Location'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp','index'=>0,],1=>['Binding'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact','Location'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/assertionConsumerService/default-sp','index'=>1,],],'contacts'=>[0=>['emailAddress'=>'example@org.hr','givenName'=>'MarkoIvančić','contactType'=>'technical',],],'entityid'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp','metadata-index'=>'https://pc-example.org.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/module.php/saml/sp/metadata.php/default-sp','metadata-set'=>'saml20-sp-remote',],'Source'=>['host'=>'localhost.someone.from.hr','privatekey'=>'key.pem','certificate'=>'cert.pem','auth'=>'example-userpass','attributes.NameFormat'=>'urn:oasis:names:tc:SAML:2.0:attrname-format:uri','authproc'=>[100=>['class'=>'core:AttributeMap',0=>'name2oid',],],'entityid'=>'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php','metadata-index'=>'https://localhost.someone.from.hr:9074/simplesamlphp/simplesamlphp-2-beta-git/saml2/idp/metadata.php','metadata-set'=>'saml20-idp-hosted',],'\\SimpleSAML\\Auth\\ProcessingChain.filters'=>[],];
        $state = new State($sampleStateArray);

        $authenticationEvent = new Event($state);

        //$dataStore->persist($authenticationEvent);

        $data = [
            'jobs_store' => $this->moduleConfiguration->getJobsStoreClass(),
            'jobs_store_needs_setup' => $jobsStoreNeedsSetup ? 'yes' : 'no',
            'jobs_store_setup_ran' => $jobsStoreSetupRan ? 'yes' : 'no',

            'data_store' => get_class($dataStore),
            'data_store_needs_setup' => $dataStoreNeedsSetup ? 'yes' : 'no',
            'data_store_setup_ran' => $dataStoreSetupRan ? 'yes' : 'no',
        ];

        die(var_dump($data));

        $template->data = $data;
        return $template;
    }

    public function jobRunner():void
    {
        $jobsStore = (new JobsStoreBuilder($this->moduleConfiguration, $this->logger))
            ->build($this->moduleConfiguration->getJobsStoreClass());


        $trackerBuilder = new AuthenticationDataTrackerBuilder($this->moduleConfiguration, $this->logger);

        $configuredTrackers = array_merge(
            [$this->moduleConfiguration->getDefaultDataTrackerAndProviderClass()],
            $this->moduleConfiguration->getAdditionalTrackers()
        );

        $start = new \DateTimeImmutable();
        $data = [
            'jobs_processed' => 0,
            'start_time' => $start->format('Y-m-d H:i:s.u'),
        ];

        /** @var $job Event\Job */
        while (($job = $jobsStore->dequeue(Event\Job::class)) !== null) {
            foreach ($configuredTrackers as $tracker) {
                ($trackerBuilder->build($tracker))->process($job->getPayload());
                $data['jobs_processed']++;
            }
        }

        $end = new \DateTimeImmutable();
        $data['end_time'] = $end->format('Y-m-d H:i:s.u');
        $data['duration'] = $end->diff($start)->format('%s seconds, %f microseconds');

        die(var_dump($data));
    }
}
