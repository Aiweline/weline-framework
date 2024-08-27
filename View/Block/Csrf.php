<?php

namespace Weline\Framework\View\Block;

use Weline\Framework\Http\Request;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Ui\FormKey;
use Weline\Framework\View\Block;

class Csrf extends Block
{
    public function __construct(protected Request $request, private FormKey $formKey, array $data = [])
    {
        parent::__construct($data);
    }
    public function getHtml(string $name): string
    {
        return $this->formKey->getHtml($this->request->getRouteUrlPath(), $name);
    }
    public function render(): string
    {
        return $this->formKey->getHtml($this->request->getRouteUrlPath());
    }
    public function __toString(): string
    {
        return $this->render();
    }
}
