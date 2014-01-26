<?php

class IndexController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('Welcome');
        parent::initialize();
    }

    public function indexAction()
    {
        if (!$this->request->isPost()) {
            $this->flash->notice('This is a notice');

            $watchlists = Watchlists::find();

//            var_dump($watchlists);
//            var_dump(serialize($watchlists));

        }
    }

    public function DeniedAction()
    {
    }
}
