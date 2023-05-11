<?php

namespace Embitel\Login\Model\ResourceModel;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\Customer\NotificationStorage;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Data\CustomerSecureFactory;
use Magento\Customer\Model\Delegation\Data\NewOperation;
use Magento\Customer\Model\Delegation\Storage as DelegatedStorage;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ImageProcessorInterface;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Magento\Customer\Model\ResourceModel\Customer;


class CustomerRepository extends \Magento\Customer\Model\ResourceModel\CustomerRepository
{
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerSecureFactory
     */
    protected $customerSecureFactory;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var AddressRepository
     */
    protected $addressRepository;

    /**
     * @var Customer
     */
    protected $customerResourceModel;

    /**
     * @var CustomerMetadataInterface
     */
    protected $customerMetadata;

    /**
     * @var CustomerSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var ImageProcessorInterface
     */
    protected $imageProcessor;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var NotificationStorage
     */
    private $notificationStorage;

    /**
     * @var DelegatedStorage
     */
    private $delegatedStorage;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @param CustomerFactory $customerFactory
     * @param CustomerSecureFactory $customerSecureFactory
     * @param CustomerRegistry $customerRegistry
     * @param AddressRepository $addressRepository
     * @param Customer $customerResourceModel
     * @param CustomerMetadataInterface $customerMetadata
     * @param CustomerSearchResultsInterfaceFactory $searchResultsFactory
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param DataObjectHelper $dataObjectHelper
     * @param ImageProcessorInterface $imageProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface $collectionProcessor
     * @param NotificationStorage $notificationStorage
     * @param DelegatedStorage|null $delegatedStorage
     * @param GroupRepositoryInterface|null $groupRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerSecureFactory $customerSecureFactory,
        CustomerRegistry $customerRegistry,
        AddressRepository $addressRepository,
        Customer $customerResourceModel,
        CustomerMetadataInterface $customerMetadata,
        CustomerSearchResultsInterfaceFactory $searchResultsFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        DataObjectHelper $dataObjectHelper,
        ImageProcessorInterface $imageProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor,
        NotificationStorage $notificationStorage,
        DelegatedStorage $delegatedStorage = null,
        ?GroupRepositoryInterface $groupRepository = null
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerSecureFactory = $customerSecureFactory;
        $this->customerRegistry = $customerRegistry;
        $this->addressRepository = $addressRepository;
        $this->customerResourceModel = $customerResourceModel;
        $this->customerMetadata = $customerMetadata;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->imageProcessor = $imageProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->collectionProcessor = $collectionProcessor;
        $this->notificationStorage = $notificationStorage;
        $this->delegatedStorage = $delegatedStorage ?? ObjectManager::getInstance()->get(DelegatedStorage::class);
        $this->groupRepository = $groupRepository ?: ObjectManager::getInstance()->get(GroupRepositoryInterface::class);
    }

    /**
     * Create or update a customer.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $passwordHash
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\InputException If bad input is provided
     * @throws \Magento\Framework\Exception\State\InputMismatchException If the provided email is already used
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function save(CustomerInterface $customer, $passwordHash = null)
    {
        /** @var NewOperation|null $delegatedNewOperation */
        $delegatedNewOperation = !$customer->getId() ? $this->delegatedStorage->consumeNewOperation() : null;
        $prevCustomerData = $prevCustomerDataArr = null;
        if ($customer->getId()) {
            $prevCustomerData = $this->getById($customer->getId());
            $prevCustomerDataArr = $prevCustomerData->__toArray();
        }
        /** @var $customer \Magento\Customer\Model\Data\Customer */
        $customerArr = $customer->__toArray();
        $customer = $this->imageProcessor->save(
            $customer,
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $prevCustomerData
        );
        $customer->setCustomAttribute("number", $customer->getNumber());
        $customer->setCustomAttribute("occupation", $customer->getCustomdropdown());
        $origAddresses = $customer->getAddresses();
        $customer->setAddresses([]);
        $customerData = $this->extensibleDataObjectConverter->toNestedArray($customer, [], CustomerInterface::class);
        $customer->setAddresses($origAddresses);
        /** @var CustomerModel $customerModel */
        $customerModel = $this->customerFactory->create(['data' => $customerData]);
        $this->populateWithOrigData($customerModel, $prevCustomerDataArr);

        //Model's actual ID field maybe different than "id" so "id" field from $customerData may be ignored.
        $customerModel->setId($customer->getId());
        $storeId = $customerModel->getStoreId();
        if ($storeId === null) {
            $customerModel->setStoreId(
                $prevCustomerData ? $prevCustomerData->getStoreId() : $this->storeManager->getStore()->getId()
            );
        }
        $this->validateGroupId($customer->getGroupId());
        $this->setCustomerGroupId($customerModel, $customerArr, $prevCustomerDataArr);
        // Need to use attribute set or future updates can cause data loss
        if (!$customerModel->getAttributeSetId()) {
            $customerModel->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
        }
        $this->populateCustomerWithSecureData($customerModel, $passwordHash);
        // If customer email was changed, reset RpToken info
        if ($prevCustomerData && $prevCustomerData->getEmail() !== $customerModel->getEmail()) {
            $customerModel->setRpToken(null);
            $customerModel->setRpTokenCreatedAt(null);
        }
        if (!array_key_exists('addresses', $customerArr)
            && null !== $prevCustomerDataArr
            && array_key_exists('default_billing', $prevCustomerDataArr)
        ) {
            $customerModel->setDefaultBilling($prevCustomerDataArr['default_billing']);
        }
        if (!array_key_exists('addresses', $customerArr)
            && null !== $prevCustomerDataArr
            && array_key_exists('default_shipping', $prevCustomerDataArr)
        ) {
            $customerModel->setDefaultShipping($prevCustomerDataArr['default_shipping']);
        }
        $this->setValidationFlag($customerArr, $customerModel);
        $customerModel->save();
        $this->customerRegistry->push($customerModel);
        $customerId = $customerModel->getId();
        if (!$customer->getAddresses()
            && $delegatedNewOperation
            && $delegatedNewOperation->getCustomer()->getAddresses()
        ) {
            $customer->setAddresses($delegatedNewOperation->getCustomer()->getAddresses());
        }
        if ($customer->getAddresses() !== null && !$customerModel->getData('ignore_validation_flag')) {
            if ($customer->getId()) {
                $existingAddresses = $this->getById($customer->getId())->getAddresses();
                $getIdFunc = function ($address) {
                    return $address->getId();
                };
                $existingAddressIds = array_map($getIdFunc, $existingAddresses);
            } else {
                $existingAddressIds = [];
            }
            $savedAddressIds = [];
            foreach ($customer->getAddresses() as $address) {
                $address->setCustomerId($customerId)
                    ->setRegion($address->getRegion());
                $this->addressRepository->save($address);
                if ($address->getId()) {
                    $savedAddressIds[] = $address->getId();
                }
            }
            $this->deleteAddressesByIds(array_diff($existingAddressIds, $savedAddressIds));
        }
        $this->customerRegistry->remove($customerId);
        $savedCustomer = $this->get($customer->getEmail(), $customer->getWebsiteId());
        $this->eventManager->dispatch(
            'customer_save_after_data_object',
            [
                'customer_data_object' => $savedCustomer,
                'orig_customer_data_object' => $prevCustomerData,
                'delegate_data' => $delegatedNewOperation ? $delegatedNewOperation->getAdditionalData() : [],
            ]
        );
        return $savedCustomer;
    }
 /**
     * Populate customer model with previous data
     *
     * @param CustomerModel $customerModel
     * @param ?array $prevCustomerDataArr
     */
    private function populateWithOrigData(CustomerModel $customerModel, ?array $prevCustomerDataArr)
    {
        if (!empty($prevCustomerDataArr)) {
            foreach ($prevCustomerDataArr as $field => $value) {
                $customerModel->setOrigData($field, $value);
            }
        }
    }
    /**
     * Validate customer group id if exist
     *
     * @param int|null $groupId
     * @return bool
     * @throws LocalizedException
     */
    private function validateGroupId(?int $groupId): bool
    {
        if ($groupId) {
            try {
                $this->groupRepository->getById($groupId);
            } catch (NoSuchEntityException $e) {
                throw new LocalizedException(__('The specified customer group id does not exist.'));
            }
        }

        return true;
    }
    /**
     * Set customer group id
     *
     * @param Customer $customerModel
     * @param array $customerArr
     * @param array $prevCustomerDataArr
     */
    private function setCustomerGroupId($customerModel, $customerArr, $prevCustomerDataArr)
    {
        if (!isset($customerArr['group_id']) && $prevCustomerDataArr && isset($prevCustomerDataArr['group_id'])) {
            $customerModel->setGroupId($prevCustomerDataArr['group_id']);
        }
    }
    /**
     * Set secure data to customer model
     *
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param string|null $passwordHash
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return void
     */
    private function populateCustomerWithSecureData($customerModel, $passwordHash = null)
    {
        if ($customerModel->getId()) {
            $customerSecure = $this->customerRegistry->retrieveSecureData($customerModel->getId());

            $customerModel->setRpToken($passwordHash ? null : $customerSecure->getRpToken());
            $customerModel->setRpTokenCreatedAt($passwordHash ? null : $customerSecure->getRpTokenCreatedAt());
            $customerModel->setPasswordHash($passwordHash ?: $customerSecure->getPasswordHash());

            $customerModel->setFailuresNum($customerSecure->getFailuresNum());
            $customerModel->setFirstFailure($customerSecure->getFirstFailure());
            $customerModel->setLockExpires($customerSecure->getLockExpires());
        } elseif ($passwordHash) {
            $customerModel->setPasswordHash($passwordHash);
        }

        if ($passwordHash && $customerModel->getId()) {
            $this->customerRegistry->remove($customerModel->getId());
        }
    }
     /**
     * Set ignore_validation_flag to skip model validation
     *
     * @param array $customerArray
     * @param Customer $customerModel
     * @return void
     */
    private function setValidationFlag($customerArray, $customerModel)
    {
        if (isset($customerArray['ignore_validation_flag'])) {
            $customerModel->setData('ignore_validation_flag', true);
        }
    }
    /**
     * Delete addresses by ids
     *
     * @param array $addressIds
     * @return void
     */
    private function deleteAddressesByIds(array $addressIds): void
    {
        foreach ($addressIds as $id) {
            $this->addressRepository->deleteById($id);
        }
    }
}
