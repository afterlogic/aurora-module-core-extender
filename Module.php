<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\CoreExtender;

/**
 * Provides user groups.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	public function init()
	{
//		$this->subscribeEvent('Core::Login::after', array($this, 'onAfterLogin'), 10);
		$this->subscribeEvent('Core::Authenticate::after', array($this, 'onAfterAuthenticate'), 10);
	}

	public function onAfterAuthenticate(&$aArgs, &$mResult)
	{
		if ($mResult && is_array($mResult) && isset($mResult['token']))
		{
			$oUser = \Aurora\System\Api::getUserById((int) $mResult['id']);
			if ($oUser instanceof \Aurora\Modules\Core\Classes\User && $oUser->isNormalOrTenant())
			{
				$sXClientHeader = (string) \MailSo\Base\Http::SingletonInstance()->GetHeader('X-Client');

				if (strtolower($sXClientHeader) !== 'webclient')
				{
					$mResult['AllowAccess'] = 1;
				}
			}
		}
	}

	public function onAfterLogin(&$aArgs, &$mResult)
	{
		if($mResult && isset($mResult['AuthToken'])) {
			$oAuthenticatedUser = \Aurora\System\Api::getAuthenticatedUser();
			if ($oAuthenticatedUser instanceof \Aurora\Modules\Core\Classes\User
					&& $oAuthenticatedUser->isNormalOrTenant()
					&& $this->isUserNotFromBusinessTenant($oAuthenticatedUser)
					// && isset($aArgs['EntryName'])
					// && strtolower($aArgs['EntryName']) === 'api'
				)
			{
				$sXClientHeader = (string) \MailSo\Base\Http::SingletonInstance()->GetHeader('X-Client');
				$bAllowMobileApps = $this->getGroupSetting($oAuthenticatedUser->EntityId, 'AllowMobileApps');
				if (strtolower($sXClientHeader) !== 'webclient' && (!is_bool($bAllowMobileApps) || !$bAllowMobileApps))
				{
					throw new \Aurora\System\Exceptions\ApiException(
						\Aurora\System\Notifications::AccessDenied,
						null,
						$this->i18N('ERROR_USER_MOBILE_ACCESS_LIMIT'),
						[],
						$this
					);

					return true;
				}
			}
		}
	}
}
