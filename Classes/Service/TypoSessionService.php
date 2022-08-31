<?php


namespace Ms3\Ms3CommerceFx\Service;


use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class TypoSessionService implements SingletonInterface
{
    public function isLoggedIn()
    {
        $user = $this->getUserRecord();
        return $user && isset($user['uid']) && $user['uid'] > 0;
    }

    /**
     * @return FrontendUserAuthentication
     */
    public function getUser()
    {
        return $GLOBALS['TSFE']->fe_user;
    }

    public function getUserRecord()
    {
        return $GLOBALS['TSFE']->fe_user->user;
    }

    public function logoutUser()
    {
        if (!$this->isLoggedIn()) return true;
        $this->doAuthentication([
            'logintype' => 'logout'
        ]);
        $ret = $this->isLoggedIn();
        return $ret;
    }

    public function loginUser($username, $password, $force = true)
    {
        if ($this->isLoggedIn()) {
            if ($force) {
                $this->logoutUser();
            } else {
                // Already logged in
                return false;
            }
        }

        $ret = $this->doAuthentication([
                'logintype' => 'login',
                'user' => $username,
                'pass' => $password
            ]);

        return $ret && $this->isLoggedIn();
    }

    private function doAuthentication($data)
    {
        $_post = $_POST;
        $_POST = $data;

        /** @var FrontendUserAuthentication $authService */
        $authService = $GLOBALS['TSFE']->fe_user;
        $authService->checkPid = false;
        $authService->start();

        $_POST = $_post;
        return !$authService->loginFailure;
    }
}
