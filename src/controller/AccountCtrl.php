<?php

namespace bronsted;

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
        return $response->withHeader('Location', '/account/index')->withStatus(302);
    }

    protected function getObjectsByUser(User $user): DbCursor
    {
        return Account::getBy(['user_uid' => $user->uid]);
    }

    protected function getObjectByUid(int $uid): ModelObject
    {
        return Account::getByUid($uid);
    }

    protected function populateObject(stdClass $data, User $user): ModelObject
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
        $account->setContent($this->config, $data);
        $account->save();
        return $account;
    }

    protected function getRouteToObject(ModelObject $selected): string
    {
        return '/account/' . $selected->uid . '/show';
    }

    protected function getRouteToObjects(ModelObject $selected): string
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
        $data = new stdClass();
        if ($selected) {
            $data = $selected->getContent($this->config);
            $data->uid = $selected->uid;
            $data->name = $selected->name;
        }
        return parent::renderForm($response, $data);
    }

    protected function render(ResponseInterface $response, DbCursor $objects, ?object $selected): MessageInterface
    {
        $data = new stdClass();
        if ($selected) {
            $data = $selected->getContent($this->config);
            $data->uid = $selected->uid;
            $data->name = $selected->name;
        }
        return parent::render($response, $objects, $data);
    }
}
