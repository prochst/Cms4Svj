<?php
declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\SmartObject;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\Identity;
use Nette\Security\AuthenticationException;
use Nette\Database\UniqueConstraintViolationException;
use App\Model\DataManager;
use App\Model\TableManager;

/**
 * Users management.
 */
final class LoginManager implements IAuthenticator
{
    /** @var DataManager */
    private $dataMng;

    /** @var TableManager */
    private $tableMng;

    /** @var Passwords */
    private $passwords;

    /** @var usersTbl */
    static string $userTbl = 'users';

    public function __construct(DataManager $dataManager, Passwords $passwords)
    {
        //parent::__construct();
        $this->tableMng = new TableManager($dataManager);
        $this->passwords = $passwords;
    }

    /**
     * Autentizuje zadaného uživatele proti zadané tabulce uživatelů
     * 
     * @param array $credentials - array[uživatel, heslo]
     * @param string $tblName - tabulka uživatelů
     * @return IIdentity
     * @throws AuthenticationException
     */
    public function authenticate(array $credentials): IIdentity
    {
        [$login, $password] = $credentials;
        $this->tableMng->readData($this::$userTbl);
        [$uid, $row] = $this->tableMng->findRow('login', $login);
        if (!$row) {
            throw new AuthenticationException('Zadali jste nesprávný email.', self::IDENTITY_NOT_FOUND);
        } elseif (!$this->passwords->verify($password, $row['pwd'])) { 
            throw new AuthenticationException('Vaše heslo není správné.', self::INVALID_CREDENTIAL);
        } elseif ($this->passwords->needsRehash($row['pwd'])) {            
            $row['pwd'] = $this->passwords->hash($password);
            $this->tableMng->updateRow($uid, $row);
        }
        return new Identity($row['login'], [$row['roleFront'], $row['roleBack']], ['name' => $row['name'], 'email' => $row['email']]);
    }
}