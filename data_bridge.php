<?php
/**
 * ORMDatatableBridge
 * 
 * A bridge between Idiorm ORM and the popular Datatables javascript library
 * that provides the ability to quickly generated the required JSON for
 * using Datatable AJAX tables.
 *
 * https://github.com/tegansnyder/idiorm-datatable-json-bridge
 *
 * BSD Licensed.
 *
 * Copyright (c) 2015, Tegan Snyder
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author   Tegan Snyder <tsnyder@tegdesign.com>
 */
class ORMDatatableBridge extends ORM {

    /**
     * @param  null|integer $id
     * @return Model
     */
    public function get_datatable($options = array()) {
    	$results = $this->_create_json(parent::find_array(), $options);
    	return $results;
    }

    /**
     * Method to create an instance of the model class
     * associated with this wrapper and populate
     * it with the supplied Idiorm instance.
     *
     * @param  array $data
     * @param  array $options
     * @return string
     */
    protected function _create_json($data, $options) {
        if ($data === false) {
            return false;
        }

        $json = array();
        $json['recordsTotal'] = count($data);
        $json['recordsFiltered'] = count($data);
        $json['data'] = array();

        $x = 0;
        foreach ($data as $row) {

        	// lets see if we have a row id we are using
        	if (isset($options['DT_RowId']['type'])) {
        		if ($options['DT_RowId']['type'] == 'fixed') {
            		// fixed keys will most likely not be used as they increment by the x
            		// value of the loop, the ideal identifier would be dynamic
        			if (isset($options['DT_RowId']['key'])) {
        				$row['DT_RowId'] = $options['DT_RowId']['key'] . $x;
        			} else {
        				// @todo exception handler
        			}
        		} elseif ($options['DT_RowId']['type'] == 'dynamic') {
        			// dynmaic keys increment by the value of the key and the value
        			// or another identifier like an id column in the dataset
        			if (isset($options['DT_RowId']['key'])) {
        				if (isset($row[$options['DT_RowId']['key']])) {
        					$row['DT_RowId'] = $row[$options['DT_RowId']['key']];
        				} else {
        					// @todo exception handler
        				}
        			}
        		}

        		if (isset($options['DT_RowId']['prepend'])) {
            		if (!empty($row['DT_RowId'])) {
            			$row['DT_RowId'] = $options['DT_RowId']['prepend'] . $row['DT_RowId'];
            		}
        		}


        	}
        	
        	/**
        	 * http://datatables.net/manual/server-side#Returned-data
        	 * Add the data contained in the object to the row using the jQuery data() method to set the data, 
        	 * which can also then be used for later retrieval (for example on a click event).
        	 */
        	if (isset($options['DT_RowData'])) {
        		if (is_array($options['DT_RowData'])) {
        			foreach ($options['DT_RowData'] as $key => $val) {

        				// lets see if we are including row data via template
        				$templated_vals = $this->getTemplatedValues($val);
						foreach ($templated_vals as $t_val) {
        					if (isset($row[$t_val])) {
        						$val = str_replace('{{' . $t_val . '}}', $row[$t_val], $val);
        					}
        				}
        				$row['DT_RowData'][$key] = $val;
        			}
        		}
        	}

        	/**
        	 * the ability to add dynamic columns to the result set is very important
        	 * for instance you may want a column called `actions` that has an edit or a delete button
        	 * this module provides a very simple way to add these
        	 */
        	if (isset($options['dynamic_columns'])) {
        		foreach ($options['dynamic_columns'] as $col) {
        			if (isset($col['key']) && isset($col['column_template'])) {
        				$templated_vals = $this->getTemplatedValues($col['column_template']);
        				foreach ($templated_vals as $t_val) {
        					if (isset($row[$t_val])) {
        						$col['column_template'] = str_replace('{{' . $t_val . '}}', $row[$t_val], $col['column_template']);
        					}
        				}
        				$row[$col['key']] = $col['column_template'];
        			} else {
        				// @todo exception handler
        			}
        		}
        	}


        	// the ability to wrap columns dyanamically with html
        	if (isset($options['wrap_columns'])) {
        		foreach ($options['wrap_columns'] as $col) {
        			if (isset($col['key']) && isset($col['column_template'])) {
        				$templated_vals = $this->getTemplatedValues($col['column_template']);
        				foreach ($templated_vals as $t_val) {
        					if (isset($row[$t_val])) {
        						$col['column_template'] = str_replace('{{' . $t_val . '}}', $row[$t_val], $col['column_template']);
        					} elseif ($t_val == 'column_data') {
        						$col['column_template'] = str_replace('{{' . $t_val . '}}', $row[$col['key']], $col['column_template']);
        					}

        				}
        				$row[$col['key']] = $col['column_template'];
        			} else {
        				// @todo exception handler
        			}
        		}
        	}

        	// ability to wrap an entire column with custom HTML
        	if (isset($options['wrap_all']['columns'])) {
        		$new_row = array();
        		foreach ($row as $key => $val) {
        			if ($key == 'DT_RowId' || $key == 'DT_RowData') {
        				continue;
        			}
        			$new_row[$key] = str_replace('{{column_data}}', $val, $options['wrap_all']['columns']);
        		}
        		foreach ($new_row as $key => $val) {
        			$row[$key] = $val;
        		}
        	}

	        if (isset($options['hide_columns'])) {
	        	foreach ($options['hide_columns'] as $hide_col) {
	        		if (isset($row[$hide_col])) {
	        			unset($row[$hide_col]);
	        		}
	        	}
	        }

	        array_push($json['data'], $row);

        	$x = $x + 1;
        }

        if (isset($options['include_columns'])) {
        	if (isset($json['data'][0])) {
	        	$columns = array();
	        	foreach ($json['data'][0] as $col => $val) {
	        		$columns[]['data'] = $col;
	        	}
	        	if (!empty($columns)) {
	        		$new_json = array();
	        		$new_json['records'] = $json;
	        		$new_json['columns'] = $columns;
	        		$json = $new_json;
	        	}
        	}
        }
        
        $json = json_encode($json);
        
        return $json;
    }

    /**
     * @param  string $str
     * @return array
     */
    private function getTemplatedValues($str) {
    	$templated_vals = array();
    	// extract a list of strings between {{tags}}
    	preg_match_all('/{{.*?}}/is', $str, $matches);
    	$matches = $matches[0];
    	foreach ($matches as $m) {
    		$m = str_replace('{{', '', $m);
    		$m = str_replace('}}', '', $m);
    		$templated_vals[] = $m;
    	}
    	return $templated_vals;
    }

    /**
     * Factory method, a repeat of content in parent::for_table, so that
     * created class is ORMDatatableBridge, not ORM
     *
     * @param  string $table_name
     * @param  string $connection_name
     * @return ORMDatatableBridge
     */
    public static function for_table($table_name, $connection_name = parent::DEFAULT_CONNECTION) {
        self::_setup_db($connection_name);
        return new self($table_name, array(), $connection_name);
    }

}