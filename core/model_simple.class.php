<?
class Model_Simple extends Model
{
    //Lazy load, true if we got the data from DB already
    protected $data_loaded = false;

    //Content of sql table of model
    protected $data = false;

    //Versions object for admin panel
    protected $versions;

    public function __construct()
    {
        //Some types can't be used in simple models
        $forbidden_types = array('parent','order','many_to_one','many_to_many','group');

        if (isset($this -> model_elements) && count($this -> model_elements))
        foreach ($this -> model_elements as $field)
        if (in_array($field[1], $forbidden_types))
        Debug :: displayError("Data type '".$field[1]."' (field '".$field[2]."') is not allowed in simple models.");
        else
        if ($field[1] == 'enum' && isset($field[3]) && array_key_exists('foreign_key', $field[3]))
        Debug :: displayError("Data type 'enum' (field '".$field[2]."') can not have 'foreign key' parameter in simple models.");

        parent :: __construct();
    }

    public function __get($key)
    {
        return $this -> getValue($key);
    }

    public function __set($key, $value)
    {
        return $this -> setValue($key, $value);
    }

    public function getDataFromDb()
    {
        $this -> data = array();

        $result = $this -> db -> query("SELECT * FROM `".$this -> table."`");

        while ($row = $this -> db -> fetch($result, "ASSOC"))
        $this -> data[$row['key']] = $row['value'];

        foreach ($this -> elements as $name => $object)
        if (!isset($this -> data[$name])) {
            $this -> db -> query("INSERT INTO `".$this -> table."`(`key`,`value`) VALUES('".$name."','')");
            $this -> data[$name] = "";
        }

        $this -> data_loaded = true;

        return $this;
    }

    public function passDataFromDb()
    {
        $this -> read($this -> data);

        return $this;
    }

    public function loadData()
    {
        if (!$this -> data_loaded) $this -> getDataFromDb();

        return $this -> data;
    }

    public function loadIntoRecord()
    {
        if (!$this -> data_loaded) $this -> getDataFromDb();

        if (count($this -> data))
        $this -> data['id'] = - 1;

        return new Record($this -> data, $this);
    }

    public function update()
    {
        if (!$this -> data_loaded) $this -> getDataFromDb(); //Load db data of this model

        $arguments = func_get_args();
        $backend   = ((isset($arguments[0]) && $arguments[0] == "backend") || $this -> getModelClass() == "seo");

        if (!$backend) //If we update model from frontend
        {
            $source = $this -> data;

            //Adds roots for files and images to check before update
            foreach ($this -> elements as $name => $object)
            if ($object -> getType() == "file" || $object -> getType() == "image")
            $source[$name] = Service :: addFileRoot($source[$name]);

            $this -> getDataFromArray($source);
        }

        $version_dump = array();

        //Does not run transaction if it's already started
        $in_transaction = (method_exists(Database :: $pdo, "inTransaction")) ? Database :: $pdo -> inTransaction() : true;

        if (!$in_transaction)
        $this -> db -> beginTransaction();

        foreach ($this -> elements as $name => $object)
        if (!$backend || ($backend && $this -> checkIfFieldEditable($name))) {
            $type = $object -> getType();

            if ($type != 'password' && $type != 'multi_images')
            $this -> prepareElementValue($object);
            else
            if ($type == 'multi_images')
            $object -> copyImages(get_class($this), $this -> data[$name]);
            else {
                $db_value = $this -> db -> getCell("SELECT `value`
                    FROM `".$this -> table."`
                    WHERE `key`='".$name."'");

                //If password was not filled when updating we get old data
                if (!$object -> getValue())
                $object -> setValue($this -> data[$name]);
                else
                if ($object -> getValue() == $db_value)
                $object -> setValue($this -> data[$name]);
                else
                $this -> prepareElementValue($object);
            }

            $param = ($type == 'date' || $type == 'date_time') ? "sql" : "";
            $value = Service :: cleanHtmlSpecialChars($object -> getValue($param));

            if ($type == 'image' || $type == 'file')
            $value = Service :: removeFileRoot($value);

            $this -> db -> query("UPDATE `".$this -> table."`
                SET `value`='".$value."'
                WHERE `key`='".$name."'");

            $version_dump[$name] = $this -> data[$name] = $value;
        }
        else
        $version_dump[$name] = $this -> data[$name];

        if ($backend) {
            $this -> versions = new Versions($this -> table, - 1); //Writes new version
            $versions_limit = $this -> getVersionsLimit();
            $this -> versions -> setLimit($versions_limit);

            //Writes to log if new version was saved or versions are disallowed
            if (!$versions_limit || $this -> versions -> save($version_dump, $this -> user))
            Log :: write($this -> getModelClass(), $this -> id, $this -> getName(), $this -> user -> getId(), "update");

            Cache :: cleanByModel($this -> getModelClass());
        }

        if (!$in_transaction)
        $this -> db -> commitTransaction();

        return $this;
    }

    public function getValue($field)
    {
        if (!$this -> data_loaded) $this -> getDataFromDb();

        if (isset($this -> data[$field]))
        return $this -> data[$field];
    }

    public function setValue($field, $value)
    {
        if (!$this -> data_loaded) $this -> getDataFromDb();

        if (isset($this -> data[$field]))
        $this -> data[$field] = trim($value);

        return $this;
    }

    public function getEnumTitle($field, $key)
    {
        if (!$this -> data_loaded) $this -> getDataFromDb();

        if (isset($this -> elements[$field]) && $this -> elements[$field] -> getType() == "enum")
        if ($key || $key === 0) {
            $values = $this -> elements[$field] -> getValuesList();

            if (array_key_exists($key, $values))
            return $values[$key];
        }
        else
        if (isset($this -> data[$field]))
        return $this -> elements[$field] -> getValueName($this -> data[$field]);
    }

    public function getSelectedEnumTitle($field)
    {
        return $this -> getEnumTitle($field, false);
    }

    public function increaseCounter($field)
    {
        if (!$this -> data_loaded) $this -> getDataFromDb();

        $arguments = func_get_args();
        $value     = (isset($arguments[1]) && $arguments[1]) ? abs(intval($arguments[1])) : 1;

        if (isset($arguments[2]) && $arguments[2] == "decrease")
        $value *= - 1;

        if (isset($this -> elements[$field]) && $this -> elements[$field] -> getType() == "int") {
            $this -> data[$field] += $value;
            $this -> update();
        }

        return $this;
    }

    public function decreaseCounter($field)
    {
        $arguments = func_get_args();
        $value     = (isset($arguments[1]) && $arguments[1]) ? abs(intval($arguments[1])) : 1;

        return $this -> increaseCounter($field, $value, "decrease");
    }

    public function combineEmails()
    {
        if (!$this -> data_loaded) $this -> getDataFromDb();

        $arguments = func_get_args();
        $fields    = (isset($arguments[0]) && count($arguments[0])) ? $arguments[0] : false;
        $emails = array();

        foreach ($this -> elements as $name => $object)
        if (!$fields || in_array($name, $fields))
        if ($object -> getType() == "email" && isset($this -> data[$name]) && $this -> data[$name])
        $emails[] = $this -> data[$name];

        return count($emails) ? implode(", ", $emails) : "";
    }

    public function __call($method, $arguments)
    {
        if ($method == "loadContent")
        return $this -> loadIntoRecord();
        else
        if ($method == "updateData")
        return $this -> update();
        else
        Debug :: displayError("Call to undefiend method '".$method."' of simple model '".get_class($this)."'.");
    }

    //TODO add functions for files and images
}
?>