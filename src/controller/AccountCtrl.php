<?php

namespace bronsted;

use Exception;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class AccountCtrl extends ModelObjectCrudCtrl
{
    private AppServiceConfig $config;

    public function __construct(AppServiceConfig $config, User $user)
    {
        parent::__construct($user);
        $this->config = $config;
    }

    public function index(ResponseInterface $response): MessageInterface
    {
        $file = dirname(__DIR__) . '/view/index.php';
        ob_start();
        require($file);
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function verify(ResponseInterface $response, Imap $imap, Smtp $smtp, int $uid): MessageInterface
    {
        $account = Account::getByUid($uid);
        $account->verify($this->config, $imap, $smtp);

        if ($account->state == Account::StateFail) {
            return parent::edit($response, $uid);
        }
        return parent::show($response, $uid);
    }

    protected function getObjectsByUser(User $user): DbCursor
    {
        return Account::getBy(['user_id' => $user->getId()]);
    }

    protected function getObjectByUid(int $uid): DbObject
    {
        return Account::getByUid($uid);
    }

    protected function populateObject(stdClass $data, User $user): DbObject
    {
        $account = null;
        if ($data->uid > 0) {
            $account = Account::getByUid($data->uid);
        }
        else {
            $account = new Account();
            $account->user_id = $user->getId();
        }
        $account->name = $data->name;
        $account->setAccountData($this->config, new AccountData($data));
        $account->save();
        return $account;
    }

    protected function getRouteToObject(DbObject $selected): string
    {
        return '/account/' . $selected->uid . '/show';
    }

    protected function getRouteToObjects(DbObject $selected): string
    {
        return '/account/user';
    }

    protected function getEditTemplate(stdClass $data): Template
    {
        return new Template('main', 'account_form', $data);
    }

    protected function getShowTemplate(stdClass $data): Template
    {
        return new Template('main', 'account_form', $data);
    }

    protected function renderForm(ResponseInterface $response, ?object $selected = null): MessageInterface
    {
        $data = null;
        if ($selected) {
            $data = $selected->getAccountData($this->config);
            $data->uid = $selected->uid;
            $data->name = $selected->name;
            $data->state = $selected->state;
            $data->state_text = $selected->state_text;
        }
        return parent::renderForm($response, $data);
    }

    protected function render(ResponseInterface $response, DbCursor $objects, ?object $selected): MessageInterface
    {
        $data = null;
        if ($selected) {
            $data = $selected->getAccountData($this->config);
            $data->uid = $selected->uid;
            $data->name = $selected->name;
            $data->state = $selected->state;
            $data->state_text = $selected->state_text;
        }
        return parent::render($response, $objects, $data);
    }
}
