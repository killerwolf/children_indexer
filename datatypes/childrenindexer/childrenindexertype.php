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

class ChildrenIndexerType extends eZDataType
{
    const DATA_TYPE_STRING = 'childrenindexer';

    function __construct()
    {
        parent::__construct( ChildrenIndexerType::DATA_TYPE_STRING, 'Children Indexer' );
    }

    function isIndexable()
    {
        return true;
    }

    function metaData( $contentObjectAttribute )
    {
        $metaDataArray = $attributes = array();
        $children = $contentObjectAttribute->object()->mainNode()->children();
        $IncludedClass = eZINI::instance('ezcade.ini')->variable('ZoneArticleSettings', 'IncludedClass');
        
        if(is_array($children))
        {
            foreach( $children as $child )
            {
            	if(!in_array( $child->classIdentifier(), $IncludedClass ) )
                {
                    continue;
                }
                $subObjectID = $child->attribute('contentobject_id');
                if ( !$subObjectID )
                    continue;
                    $object = eZContentObject::fetch( $subObjectID );
                    if ( eZContentObject::recursionProtect( $subObjectID ) )
                    {
                        if ( !$object )
                        {
                            continue;
                        }
                        $attributes = $object->contentObjectAttributes( true);
                    }
                $attributeMetaDataArray = eZContentObjectAttribute::metaDataArray( $attributes );
                $metaDataArray = array_merge( $metaDataArray, $attributeMetaDataArray );
            }
        }
        return $metaDataArray;
    }
}

eZDataType::register( ChildrenIndexerType::DATA_TYPE_STRING, 'childrenindexertype' );

?>
