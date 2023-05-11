<?php
namespace Embitel\Login\Controller\Account;

use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;

class LoginPost extends \Magento\Customer\Controller\Account\LoginPost
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        CustomerUrl $customerHelperData,
        Validator $formKeyValidator,
        AccountRedirect $accountRedirect,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory
    ) {
        $this->_customerFactory = $customerFactory;
        parent::__construct($context,$customerSession,$customerAccountManagement,$customerHelperData,$formKeyValidator,$accountRedirect);
    }

    public function execute()
    {       
        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->getRequest())) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }
        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');

            if(!strpos($login['username'], '@') !== false ) {
                $isMobile = $login['username']; 
                /* Get email id based on mobile number and login*/
                $customereCollection = $this->_customerFactory->create();
                $customereCollection->addFieldToFilter("number", $login['username']);
                foreach($customereCollection as $customerdata){ 
                    $login['username'] = $customerdata['email'];
                }
            }

            
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                    $this->session->setCustomerDataAsLoggedIn($customer);
                    $this->session->regenerateId();
                    if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                        $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                        $metadata->setPath('/');
                        $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                    }
                    $redirectUrl = $this->accountRedirect->getRedirectCookie();

                    if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {

                        $this->accountRedirect->clearRedirectCookie();
                        $resultRedirect = $this->resultRedirectFactory->create();
                        // URL is checked to be internal in $this->_redirect->success()
                        $resultRedirect->setUrl($this->_redirect->success($redirectUrl));
                        return $resultRedirect;
                    }else{
                        $resultRedirect = $this->resultRedirectFactory->create();
                        $resultRedirect->setPath('');
                        return $resultRedirect;
                    }
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $message = __(
                        'This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.',
                        $value
                    );
                    $this->messageManager->addError($message);
                    $this->session->setUsername($login['username']);
                } catch (UserLockedException $e) {
                    $message = __(
                        'You did not sign in correctly or your account is temporarily disabled.'
                    );
                    $this->messageManager->addError($message);
                    $this->session->setUsername($login['username']);
                } catch (AuthenticationException $e) {
                    $message = __('You did not sign in correctly or your account is temporarily disabled.');
                    $this->messageManager->addError($message);
                    $this->session->setUsername($login['username']);
                } catch (LocalizedException $e) {
                    $message = $e->getMessage();
                    $this->messageManager->addError($message);
                    $this->session->setUsername($login['username']);
                } catch (\Exception $e) {
                    // PA DSS violation: throwing or logging an exception here can disclose customer password
                    $this->messageManager->addError(
                        __('An unspecified error occurred. Please contact us for assistance.')
                    );
                }
            } else {
                $this->messageManager->addError(__('A login and a password are required.'));
            }
        }

        return $this->accountRedirect->getRedirect();
    }
}