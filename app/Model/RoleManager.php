<?php
declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\Authorizator;

class RoleManager implements Nette\Security\Authorizator
{
    const ROLE_VYBOR = 'Výbor';
    const ROLE_CLEN = 'Člen';
    const ROLE_HOST = 'Host';
    const ROLE_ADMIN = 'Admin';
    const ROLE_EDITOR = 'Editor';
    const ROLE_NONE = 'Žádný';
    const RES_OBSAH = 'Obsah';
    const RES_NASTAV = 'Nastavení';
    const RES_CLEN = 'Člen';
    const RES_VYBOR = 'Výbor';
    const RES_DENYTBL = 'users svj_members params';

	/**
     * Vyhodnotí zda role uživatel je oprávněna k zadanému zdroji a operaci
     * 
     * @param $role - všechny role uživatele je převzata z $user->identity
     * @param $resource - zdroj kam má být přístup - zadává se při volání funkce
     * @param $operation - nepoužívá se
     */
    public function isAllowed($role, $resource, $operation): bool
	{		
        if ($role === self::ROLE_ADMIN) {
			return true;
		}
        if ($role == self::ROLE_EDITOR && $resource === self::RES_OBSAH) {
			return true;
		}
        if ($role == self::ROLE_CLEN && $resource === self::RES_CLEN) {
			return true;
		}
        if ($role == self::ROLE_VYBOR && ($resource === self::RES_CLEN || $resource === self::RES_VYBOR)) {
			return true;
		}

		return false;
	}
}