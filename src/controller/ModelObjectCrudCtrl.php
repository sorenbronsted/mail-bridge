<?php

namespace bronsted;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Exception;
use stdClass;

abstract class ModelObjectCrudCtrl
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getByUser(ResponseInterface $response): MessageInterface
    {
        $objects = $this->getObjectsByUser($this->user);
        $selected = $objects[0] ?? null;
        return $this->render($response, $objects, $selected);
    }

    public function create(ResponseInterface $response): MessageInterface
    {
        return $this->renderForm($response);
    }

    public function show(ResponseInterface $response, int $uid): MessageInterface
    {
        $objects = $this->getObjectsByUser($this->user);
        $selected = $this->getObjectByUid($uid);
        return $this->render($response, $objects, $selected);
    }

    public function edit(ResponseInterface $response, int $uid): MessageInterface
    {
        $selected = $this->getObjectByUid($uid);
        return $this->renderForm($response, $selected);
    }

    public function delete(ResponseInterface $response, int $uid): MessageInterface
    {
        $selected = $this->getObjectByUid($uid);
        $selected->delete();
        return $response->withHeader('Location', $this->getRouteToObjects($selected))->withStatus(302);
    }

    public function save(ServerRequestInterface $request, ResponseInterface $response): MessageInterface
    {
        $data = (object)$request->getParsedBody();
        $object = $this->populateObject($data, $this->user);
        if ($object->uid <= 0) {
            throw new Exception('Object must have an uid');
        }
        return $response->withHeader('Location', $this->getRouteToObject($object))->withStatus(302);
    }

    protected function renderForm(ResponseInterface $response, ?object $selected = null): MessageInterface
    {
        $data = new stdClass();
        $data->selected = $selected;
        $data->uiState = 'edit';
        $template =  $this->getEditTemplate($data);
        $response->getBody()->write(json_encode($template->render()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    protected function render(ResponseInterface $response, DbCursor $objects, ?object $selected): MessageInterface
    {
        $data = new stdClass();
        $data->user = $this->user;
        $data->uiState = 'show';
        $data->objects = $objects;
        $data->selected = $selected;
        $template = $this->getShowTemplate($data);
        $response->getBody()->write(json_encode($template->render()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    abstract protected function getObjectsByUser(User $user): DbCursor;
    abstract protected function getObjectByUid(int $uid): DbObject;
    abstract protected function populateObject(stdClass $data, User $user): DbObject;
    abstract protected function getRouteToObject(DbObject $selected): string;
    abstract protected function getRouteToObjects(DbObject $selected): string;
    abstract protected function getEditTemplate(stdClass $data): Template;
    abstract protected function getShowTemplate(stdClass $data): Template;
}
