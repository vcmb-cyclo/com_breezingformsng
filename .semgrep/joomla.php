<?php

// Test cases for .semgrep/joomla.yml, run with: semgrep --test .semgrep/

class ExampleModel
{
    public function sql($app, $db, $query)
    {
        $name = $app->getInput()->getString('name');
        $id   = $app->getInput()->get('id');

        // ruleid: bfng-input-to-sql
        $db->setQuery('SELECT * FROM #__x WHERE name = ' . $name);
        // ruleid: bfng-input-to-sql
        $query->where('a.name = ' . $name);
        // ruleid: bfng-input-to-sql
        $query->order($id . ' DESC');

        // ok: bfng-input-to-sql
        $query->where('a.name = ' . $db->quote($name));
        // ok: bfng-input-to-sql
        $query->where('a.id = ' . (int) $id);
        // ok: bfng-input-to-sql
        $query->where('a.name = :name')->bind(':name', $name);
        // ok: bfng-input-to-sql
        $query->order($db->quoteName($id));
    }

    public function output($app)
    {
        $search = $app->getInput()->getString('search');

        // ruleid: bfng-input-to-output
        echo $search;
        // ruleid: bfng-input-to-output
        echo '<p>' . $search . '</p>';

        // ok: bfng-input-to-output
        echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
        // ok: bfng-input-to-output
        echo $this->escape($search);
        // ok: bfng-input-to-output
        echo (int) $search;
    }

    public function superglobals()
    {
        // ruleid: bfng-superglobal-access
        $a = $_GET['a'];
        // ruleid: bfng-superglobal-access
        $b = $_POST['b'];
    }

    public function serialized($data)
    {
        // ruleid: bfng-unserialize-without-allowed-classes
        $x = unserialize($data);
        // ok: bfng-unserialize-without-allowed-classes
        $y = unserialize($data, ['allowed_classes' => false]);
    }
}

class ExampleController extends BaseController
{
    // ruleid: bfng-controller-task-without-token
    public function remove()
    {
        $model = $this->getModel();
        $model->delete($this->input->get('cid', [], 'array'));
        $this->setRedirect('index.php');
    }

    // ok: bfng-controller-task-without-token
    public function save()
    {
        $this->checkToken();
        $model = $this->getModel();
        $model->save($this->input->post->get('jform', [], 'array'));
    }

    // ok: bfng-controller-task-without-token
    public function preview()
    {
        $this->setRedirect('index.php');
    }
}

class ExampleNotInput
{
    public function params($params, $db)
    {
        $value = $params->get('table');
        // ok: bfng-input-to-sql
        $db->setQuery('SELECT * FROM ' . $value);
    }

    public function postInput($app, $db)
    {
        $post = $app->getInput()->post;
        $name = $post->getString('name');
        // ruleid: bfng-input-to-sql
        $db->setQuery("SELECT * FROM #__x WHERE name = '" . $name . "'");
    }
}

class ExampleBoundQuery
{
    public function bound($app, $db)
    {
        $token = $app->getInput()->getString('token', '');
        $query = $db->createQuery()
            ->update($db->quoteName('#__x'))
            ->where($db->quoteName('token') . ' = :token')
            ->bind(':token', $token);
        // ok: bfng-input-to-sql
        $db->setQuery($query);
    }

    public function helper($app, $builder)
    {
        $input = $app->getInput();
        // ok: bfng-input-to-output
        echo $builder->routing($input->getString('return', ''));
    }
}

class ExampleListController extends AdminController
{
    // ok: bfng-controller-task-without-token
    public function publish()
    {
        $ids = $this->input->get('cid', [], 'array');
        parent::publish();
        $model = $this->getModel();
        $model->publish($ids, 1);
    }
}
