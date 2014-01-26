<?php

/**
 * Elements
 *
 * Helps to build UI elements for the application
 */
class Elements extends Phalcon\Mvc\User\Component
{

    private $_adminMenu = array(
        'main-menu' => array(
            'etsyusers' => array(
                'caption' => 'Users',
                'action' => 'index'
            ),
            'watchlists' => array(
                'caption' => 'Watchlists',
                'action' => 'index'
            ),
            'parameters' => array(
                'caption' => 'Parameters',
                'action' => 'index'
            ),
        ),
        'signin-menu' => array(
            'login' => array(
                'caption' => 'Log out',
                'action' => 'end'
            ),
        )
    );

    /**
     * Builds header menu with left and right items
     *
     * @return string
     */
    public function outputAdminMenu()
    {

        $auth = $this->session->get('auth');
        if (!$auth) {
            return '';
        }

        echo '<nav>';
        $controllerName = $this->view->getControllerName();
        foreach ($this->_adminMenu as $menuClassName => $menu) {
            echo '<ul class="', $menuClassName, '">';
            foreach ($menu as $controller => $option) {
                if ($controllerName == $controller) {
                    echo '<li class="active">';
                } else {
                    echo '<li>';
                }
                $url = ($controller=='index'?'':$controller).($option['action']=='index'?'':'/'.$controller);
                echo Phalcon\Tag::linkTo($url, $option['caption']);
                echo '</li>';
            }
            echo '</ul>';
        }
        echo '</nav>';
    }
}
