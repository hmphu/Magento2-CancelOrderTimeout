<?php
namespace UgoRaffaele\CancelOrderTimeout\Cron;

class CancelOrder {
	
	protected $scopeConfig;
	protected $orderRepository;
	protected $searchCriteriaBuilder;
	protected $orderManagement;
	protected $date;
	protected $logger;
	
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
		\Magento\Sales\Api\OrderManagementInterface $orderManagement,
		\Magento\Framework\Stdlib\DateTime\DateTime $date,
		\Psr\Log\LoggerInterface $logger
    ){
		parent::__construct($context);
		$this->scopeConfig = $scopeConfig;
		$$this->orderRepository = $orderRepository;
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
		$this->orderManagement = $orderManagement;
		$this->date = $date;
		$this->logger = $logger;
    }
	
	public function isModuleEnabled()
	{
		$moduleEnabled = $this->scopeConfig->getValue('cancelordertimeout/general/enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		return $moduleEnabled;
	}
	
	public function getTimeout()
	{
		$timeout = $this->scopeConfig->getValue('cancelordertimeout/general/timeout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		return intval($timeout);
	}
	
	public function execute()
	{
		
		if ($this->isModuleEnabled()) {
			
			$agoDate = $this->date->gmtDate(null, strtotime("-{$this->getTimeout()} minutes"));
			$this->logger->info('Checking orders older than {$agoDate}');
			
			$searchCriteria = $this->searchCriteriaBuilder
				->addFilter('created_at', $agoDate, 'gt')
				->addFilter('status', 'pending', 'eq')
				->create();
			$orders = $this->orderRepository->getList($searchCriteria);
		
			foreach ($orders->getItems() as $order) {
				$this->logger->info('Cancelling Order # {$order->getEntityId()}');
				$this->orderManagement->cancel($order->getEntityId());
			};
			
		}
		
	}

}