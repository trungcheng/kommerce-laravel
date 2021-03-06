<?php

namespace App\Http\Controllers;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManager;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use inklabs\kommerce\Action\Cart\CreateCartCommand;
use inklabs\kommerce\Action\Cart\GetCartBySessionIdQuery;
use inklabs\kommerce\Action\Cart\GetCartQuery;
use inklabs\kommerce\Action\Cart\Query\GetCartBySessionIdRequest;
use inklabs\kommerce\Action\Cart\Query\GetCartBySessionIdResponse;
use inklabs\kommerce\Action\Cart\Query\GetCartRequest;
use inklabs\kommerce\Action\Cart\Query\GetCartResponse;
use inklabs\kommerce\EntityDTO\Builder\DTOBuilderFactory;
use inklabs\kommerce\EntityDTO\Builder\DTOBuilderFactoryInterface;
use inklabs\kommerce\EntityDTO\CartDTO;
use inklabs\kommerce\EntityDTO\OrderAddressDTO;
use inklabs\kommerce\EntityDTO\UserDTO;
use inklabs\kommerce\EntityRepository\RepositoryFactory;
use inklabs\kommerce\Exception\EntityNotFoundException;
use inklabs\kommerce\Lib\CartCalculator;
use inklabs\kommerce\Lib\Command\CommandBus;
use inklabs\kommerce\Lib\Command\CommandBusInterface;
use inklabs\kommerce\Lib\Command\CommandInterface;
use inklabs\kommerce\Lib\DoctrineHelper;
use inklabs\kommerce\Lib\Event\EventDispatcher;
use inklabs\kommerce\Lib\Event\EventDispatcherInterface;
use inklabs\kommerce\Lib\FileManagerInterface;
use inklabs\kommerce\Lib\LocalFileManager;
use inklabs\kommerce\Lib\Mapper;
use inklabs\kommerce\Lib\MapperInterface;
use inklabs\kommerce\Lib\PaymentGateway\FakePaymentGateway;
use inklabs\kommerce\Lib\PaymentGateway\PaymentGatewayInterface;
use inklabs\kommerce\Lib\Pricing;
use inklabs\kommerce\Lib\Query\QueryBus;
use inklabs\kommerce\Lib\Query\QueryBusInterface;
use inklabs\kommerce\Lib\Query\QueryInterface;
use inklabs\kommerce\Lib\ShipmentGateway\EasyPostGateway;
use inklabs\kommerce\Lib\ShipmentGateway\ShipmentGatewayInterface;
use inklabs\kommerce\Service\ServiceFactory;
use inklabs\KommerceTemplates\Lib\TwigTemplate;
use Twig_Environment;

class Controller extends BaseController
{
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var Pricing */
    private $pricing;

    /** @var CartCalculator */
    private $cartCalculator;

    /** @var EntityManager */
    private $entityManager;

    /** @var CommandBusInterface */
    private $commandBus;

    /** @var QueryBusInterface */
    private $queryBus;

    /** @var MapperInterface */
    private $mapper;

    /** @var RepositoryFactory */
    private $repositoryFactory;

    /** @var ServiceFactory */
    private $serviceFactory;

    /** @var DTOBuilderFactoryInterface */
    private $DTOBuilderFactory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var PaymentGatewayInterface */
    private $paymentGateway;

    /** @var  ShipmentGatewayInterface */
    private $shipmentGateway;

    /** @var FileManagerInterface */
    private $fileManager;

    /** @var CartDTO */
    private $cartDTO;

    /** @var UserDTO */
    private $userDTO;

    public function __construct()
    {
        $this->setupKommerce();
    }

    protected function getPricing()
    {
        return $this->pricing;
    }

    protected function getCartCalculator()
    {
        return $this->cartCalculator;
    }

    protected function dispatch(CommandInterface $command)
    {
        $this->commandBus->execute($command);
    }

    protected function dispatchQuery(QueryInterface $query)
    {
        $this->queryBus->execute($query);
    }

    private function setupKommerce()
    {
        $this->setupDoctrine();
        $this->setupPricing();
        $this->setupRepositoryFactory();
        $this->setupEventDispatcher();
        $this->setupPaymentGateway();
        $this->setupFileManager();
        $this->setupShipmentGateway();
        $this->setupServiceFactory();
        $this->setupDTOBuilderFactory();
        $this->setupMapper();
        $this->setupCommandBus();
        $this->setupQueryBus();
    }

    private function setupDoctrine()
    {
        $cacheDriver = $this->getCacheDriver();
        $this->doctrineHelper = new DoctrineHelper($cacheDriver);
        $this->doctrineHelper->setup([
            'driver' => 'pdo_sqlite',
            'path' => storage_path() . '/db.sqlite',
        ]);
        $this->doctrineHelper->addSqliteFunctions();

        $this->entityManager = $this->doctrineHelper->getEntityManager();
    }

    private function getCacheDriver()
    {
        return new ArrayCache();
    }

    private function setupPricing()
    {
        $this->pricing = new Pricing();
        $this->cartCalculator = new CartCalculator($this->pricing);
    }

    private function setupRepositoryFactory()
    {
        $this->repositoryFactory = new RepositoryFactory(
            $this->entityManager
        );
    }

    private function setupEventDispatcher()
    {
        $this->eventDispatcher = new EventDispatcher();
    }

    private function setupPaymentGateway()
    {
        $this->paymentGateway = new FakePaymentGateway();
    }

    private function setupFileManager()
    {
        $this->fileManager = new LocalFileManager(
            storage_path() . '/files'
        );
    }

    private function setupShipmentGateway()
    {
        $storeAddress = new OrderAddressDTO();
        $storeAddress->zip5 = '90401';

        $this->shipmentGateway = new EasyPostGateway(
            'api-key',
            $storeAddress
        );
    }

    private function setupServiceFactory()
    {
        $this->serviceFactory = new ServiceFactory(
            $this->repositoryFactory,
            $this->cartCalculator,
            $this->eventDispatcher,
            $this->paymentGateway,
            $this->shipmentGateway,
            $this->fileManager
        );
    }

    private function setupDTOBuilderFactory()
    {
        $this->DTOBuilderFactory = new DTOBuilderFactory();
    }

    private function setupMapper()
    {
        $this->mapper = new Mapper(
            $this->serviceFactory,
            $this->pricing,
            $this->DTOBuilderFactory
        );
    }

    private function setupCommandBus()
    {
        $this->commandBus = new CommandBus(
            $this->mapper
        );
    }

    private function setupQueryBus()
    {
        $this->queryBus = new QueryBus(
            $this->mapper
        );
    }

    /**
     * @return string
     */
    private function getRemoteIP4()
    {
        return request()->ip();
    }

    /**
     * @return string
     */
    private function getSessionId()
    {
        return session()->getId();
    }

    /**
     * @return \Illuminate\Session\Store
     */
    private function getSession()
    {
        return session();
    }

    /**
     * @return CartDTO
     */
    protected function getCart()
    {
        $cartId = $this->getCartId();

        $request = new GetCartRequest($cartId);
        $response = new GetCartResponse($this->getCartCalculator());
        $this->dispatchQuery(new GetCartQuery($request, $response));

        return $response->getCartDTOWithAllData();
    }

    /**
     * @return string
     */
    protected function getCartId()
    {
        if ($this->cartDTO != null) {
            return $this->cartDTO->id->getHex();
        }

        try {
            $cartDTO = $this->getCartFromSession();
            return $cartDTO->id->getHex();
        } catch (EntityNotFoundException $e) {
        }

        $this->createNewCart();

        return $this->cartDTO->id->getHex();
    }

    /**
     * @return CartDTO
     * @throws EntityNotFoundException
     */
    private function getCartFromSession()
    {
        $request = new GetCartBySessionIdRequest($this->getSessionId());
        $response = new GetCartBySessionIdResponse($this->cartCalculator);
        $this->dispatchQuery(new GetCartBySessionIdQuery($request, $response));

        return $response->getCartDTO();
    }

    protected function createNewCart()
    {
        $userId = null;
        if ($this->userDTO !== null) {
            $userId = $this->userDTO->id->getHex();
        }

        $createCartCommand = new CreateCartCommand(
            $this->getRemoteIP4(),
            $userId,
            $this->getSessionId()
        );
        $this->dispatch($createCartCommand);
        $cartId = $createCartCommand->getCartId();

        $request = new GetCartRequest($cartId);
        $response = new GetCartResponse($this->getCartCalculator());
        $this->dispatchQuery(new GetCartQuery($request, $response));

        $this->cartDTO = $response->getCartDTO();
    }

    protected function displayTemplate($name, $context)
    {
        $twig = $this->getTwig();

        $session = $this->getSession();
        if ($session->isStarted()) {
            $twig->addGlobal('flashMessages', $session->get('flashMessages'));
        }

        $twig->display($name, $context);
    }

    /**
     * @return Twig_Environment
     */
    protected function getTwig()
    {
        $twigTemplate = new TwigTemplate();
        $twigTemplate->enableDebug();

        return $twigTemplate->getTwigEnvironment();
    }

    /**
     * @param Request $request
     * @param string $message
     */
    protected function flashSuccess(Request $request, $message = '')
    {
        $this->flashMessage($request, 'success', $message);
    }

    /**
     * @param Request $request
     * @param $message
     */
    protected function flashError(Request $request, $message = '')
    {
        $this->flashMessage($request, 'danger', $message);
    }

    /**
     * @param Request $request
     * @param string $type
     * @param string $message
     */
    private function flashMessage(Request $request, $type, $message = '')
    {
        $messages = $request->session()->get('flashMessages', []);
        $messages[$type][] = $message;
        $request->session()->flash('flashMessages', $messages);
    }
}
