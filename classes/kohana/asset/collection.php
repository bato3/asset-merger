<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Collection of assets
 *
 * @package    Despark/asset-merger
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Kohana_Asset_Collection implements Iterator, Countable, ArrayAccess {

    /**
     * @var  array  assets
     */
    protected $_assets = array();

    /**
     * @var  string  name
     */
    protected $_name;

    /**
     * @var  string  type
     */
    protected $_type;

    /**
     * @var  string  asset file
     */
    protected $_destination_file;

    /**
     * @var   string  web file
     */
    protected $_destination_web;

    /**
     * @var  int  last modified time
     */
    protected $_last_modified = NULL;

    public function destination_file()
    {
        $file = '';
        //return $this->_destination_file;
        foreach ($this->assets() as $asset) {
            $file .= $asset->source_file().$asset->last_modified();
        }
        return Assets::file_path($this->_type, sha1($file).'.'.$this->_type);
    }

    public function destination_web()
    {
        //return $this->_destination_web;
        //Filename is SHA1 sum of all source filenames, and their timestamps, so every time you add new/change
        //file to group new file will be created.
        $file = '';
        foreach ($this->assets() as $asset) {
            $file .= $asset->source_file().$asset->last_modified();
        }
        return Assets::web_path($this->_type, sha1($file).'.'.$this->_type);
    }

    public function create_destination_dir()
    {
        if (!is_dir(dirname($this->destination_file())))
        {
            // Create directory for destination file
            mkdir(dirname($this->destination_file()), 0777, TRUE);
        }
    }

    public function type()
    {
        return $this->_type;
    }

    public function name()
    {
        return $this->_name;
    }

    public function assets()
    {
        return $this->_assets;
    }

    /**
     * Set up environment
     *
     * @param  string  $type
     * @param  string  $name
     */
    public function __construct($type, $name = 'all')
    {
        // Check type
        Assets::require_valid_type($type);

        // Set type and name
        $this->_type = $type;
        $this->_name = $name;

        // Set asset file and web file
        $this->_destination_file = Assets::file_path($type, $name.'.'.$type);
        $this->_destination_web  = Assets::web_path($type, $name.'.'.$type);
    }

    /**
     * Compile asset content
     *
     * @param   bool  $process
     * @return  string
     */
    public function compile($process = FALSE)
    {
        // Set content
        $content = '';

        foreach ($this->assets() as $asset)
        {
            // Add comment to content
            $content .= "/* File: ".$asset->destination_web()." Compiled at: ".date("Y-m-d H:i:s")."*/\n";
            // Compile content
            $content .= $asset->compile(FALSE)."\n"; //false because we should not process files before merging, this usualy ends up with unusable js
        }

        //Check if we should process merged files
        if (in_array(Kohana::$environment, (array) Kohana::$config->load('asset-merger.process'))) {
            //Process combined file with default processor
            $content = Asset_Processor::process(Kohana::$config->load('asset-merger.processor.'.$this->type()), $content);
        }

        return $content;
    }

    /**
     * Render HTML
     *
     * @param   bool  $process
     * @return  string
     */
    public function render($process = FALSE)
    {
        if ($this->needs_recompile())
        {
            $this->create_destination_dir();
            // Recompile file
            file_put_contents($this->destination_file(), $this->compile($process));
        }
        return Asset::html($this->type(), $this->destination_web(), $this->last_modified());
    }

    /**
     * Render inline HTML
     *
     * @param   bool  $process
     * @return  string
     */
    public function inline($process = FALSE)
    {
        return Asset::html_inline($this->type(), $this->compile($process));
    }

    /**
     * Determine if recompilation is needed
     *
     * @return bool
     */
    public function needs_recompile()
    {
        return Assets::is_modified_later($this->destination_file(), $this->last_modified());
    }

    /**
     * Get and set the last modified time
     *
     * @return integer
     */
    public function last_modified()
    {
        if ($this->_last_modified === NULL)
        {
            // Get last modified times
            $last_modified_times = array_filter(self::_invoke($this->assets(), 'last_modified'));

            if ( ! empty($last_modified_times))
            {
                // Set the last modified time
                $this->_last_modified = max($last_modified_times);
            }
        }

        return $this->_last_modified;
    }

    static public function _invoke($arr, $method)
    {
        $new_arr = array();

        foreach ($arr as $id => $item)
        {
            $new_arr[$id] = $item->$method();
        }

        return $new_arr;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset))
        {
            $this->_assets[] = $value;
        }
        else
        {
            $this->_assets[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->_assets[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_assets[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_assets[$offset]) ? $this->_assets[$offset] : NULL;
    }

    public function rewind()
    {
        reset($this->_assets);
    }

    public function current()
    {
        return current($this->_assets);
    }

    public function key()
    {
        return key($this->_assets);
    }

    public function next()
    {
        return next($this->_assets);
    }

    public function valid()
    {
        return $this->current() !== FALSE;
    }

    public function count()
    {
        return count($this->_assets);
    }

} // End Asset_Collection