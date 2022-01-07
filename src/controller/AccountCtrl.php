<?php

namespace bronsted;

use Exception;
use HansOtt\PSR7Cookies\SetCookie;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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

    public function loginToken(ServerRequestInterface $request, ResponseInterface $response): MessageInterface
    {
        $params = (object)$request->getQueryParams();
        if (!isset($params->id)) {
            return $response->withStatus(422);
        }
        $user = User::getOneBy(['id' => $params->id]);
        $token = Crypto::encrypt($user->uid, $this->config->key);
        $response->getBody()->write(json_encode(['token' => $token]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): MessageInterface
    {
        $params = (object)$request->getQueryParams();
        if (!isset($params->token)) {
            return $response->withStatus(422);
        }

        $uid = Crypto::decrypt($params->token, $this->config->key);
        $user = User::getByUid($uid);
        //TODO P2 jwt cookie
        $cookie = new SetCookie($this->config->cookieName, $user->uid, time() + 60 * 60 * 24 * 30 * 12, '/', 'localhost', true, true, 'lax');
        $response = $cookie->addToResponse($response);
        return $response->withHeader('Location', '/account')->withStatus(302);
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
        return Account::getBy(['user_uid' => $user->uid]);
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
            $account->user_uid = $user->uid;
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
