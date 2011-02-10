<?php
//
// ChildrenIndexer - extension for eZ Publish
// Copyright (C) 2008 Seeds Consulting AS, http://www.seeds.no/
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of version 2.0 of the GNU General
// Public License as published by the Free Software Foundation.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
// MA 02110-1301, USA.
//

class ReindexParentType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_ID = 'reindexparent';

    function __construct()
    {
        parent::__construct( ReindexParentType::WORKFLOW_TYPE_ID, 'Reindex parent node' );
    }

    function execute( $process, $event )
    {
        $parameters = $process->attribute( 'parameter_list' );
       
        if(isset($parameters['object_id']))
        {
        	$sqlFilter = "WHERE contentobject_id=".$parameters['object_id'];
        }
        elseif (isset($parameters['node_list']) && is_array( $parameters['node_list'] ) ) 
        {
        	$sqlFilter = "WHERE node_id IN (".implode(',', $parameters['node_list'] ).")";
        }
        
        $objectID = $parameters['object_id'];

        $db = eZDB::instance();

        $pathString = $db->arrayQuery( "SELECT path_string FROM ezcontentobject_tree $sqlFilter" );
        
        if ( $pathString )
        {
        	$pathString2 =array();
        	foreach ($pathString as $elt)
        	{
        		$pathString2 = array_merge($pathString2, explode( '/', trim( $elt['path_string'], '/' ) ) );
        	}
        	$pathString2 = array_unique($pathString2);
        	rsort($pathString2);
            $path = array_reverse( $pathString2 );
            array_shift( $path );
            // $path now contains node IDs of all ancestors (starting with the parent node)
            foreach( $path as $ancestorNodeID )
            {
                /* Find object ID but only if the ancestor's object contains searchable
                   attribute of the childrenindexer datatype. */
                $ancestorObjectID = $db->arrayQuery( "SELECT a.contentobject_id
                                                      FROM ezcontentobject_tree t,
                                                           ezcontentobject_attribute a,
                                                           ezcontentclass_attribute ca
                                                      WHERE t.node_id=$ancestorNodeID
                                                        AND t.contentobject_id=a.contentobject_id
                                                        AND t.contentobject_version=a.version
                                                        AND a.contentclassattribute_id=ca.id
                                                        AND ca.version=0
                                                        AND ca.data_type_string='childrenindexer'
                                                        AND ca.is_searchable=1", array( 'limit' => 1 ) );
                if ( !$ancestorObjectID )
                {
                    continue;
                }
                
                $ancestorObjectID = $ancestorObjectID[0]['contentobject_id'];
                eZContentOperationCollection::registerSearchObject( $ancestorObjectID, false );
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( ReindexParentType::WORKFLOW_TYPE_ID, 'reindexparenttype' );

?>
