<?php
//
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Find
// SOFTWARE RELEASE: 2.2-0
// COPYRIGHT NOTICE: Copyright (C) 1999-2010 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezfsolrdocumentfieldxml.php
*/

/*!
  \class ezfSolrDocumentFieldXML ezfsolrdocumentfieldobjectrelation.php
  \brief The class ezfSolrDocumentFieldObjectRelation does

*/

class ezfSolrDocumentFieldChildrenIndex extends ezfSolrDocumentFieldObjectRelation
{

    public function strip_html_tags( $text )
    {
        $text = preg_replace(
            array(
            // Replace ezmatrix specific cell and column tags by a space
            '@<c[^>]*?>(.*?)</c>@siu',
            '@<column[^>]*?>(.*?)</column>@siu',
            // Remove invisible content
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noframes[^>]*?.*?</noframes>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
            // Add line breaks before and after blocks
            '@</?((address)|(blockquote)|(center)|(del))@iu',
            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
            '@</?((table)|(th)|(td)|(caption))@iu',
            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
            '@</?((frameset)|(frame)|(iframe))@iu',
            '@</?(br)@iu'
            ),
            array(
            ' $0 ', ' $0 ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
            "\n\$0", "\n\$0", "\n"
            ),
            $text );
        return strip_tags( $text );
    }


    /**
     * @see ezfSolrDocumentFieldBase::getData()
     */
    public function getData()
    {
        
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );
        $fieldName = self::getFieldName( $contentClassAttribute );
        
        $contentObjectID = $this->ContentObjectAttribute->attribute( 'contentobject_id' );

        $mainNode = eZContentObject::fetch($contentObjectID)->mainNode();
        $children = $mainNode->children();
        
        switch ( $contentClassAttribute->attribute( 'data_type_string' ) )
        {
            case 'childrenindexer' :
            {
                $IncludedClass = eZINI::instance('ezcade.ini')->variable('ZoneArticleSettings', 'IncludedClass');
                $returnArray = array();
                foreach( $children as $child )
                {
                    if(!in_array( $child->classIdentifier(), $IncludedClass ) )
                    {
                        continue;
                    }
                    // 1st create aggregated metadata fields
                    $metaAttributeValues = eZSolr::getMetaAttributesForObject( $child->object() );
                    foreach ( $metaAttributeValues as $metaInfo )
                    {
                        $submetaFieldName = ezfSolrDocumentFieldBase::generateSubmetaFieldName( $metaInfo['name'], $contentClassAttribute );
                        if ( isset( $returnArray[$submetaFieldName] ) )
                        {
                            $returnArray[$submetaFieldName] = array_merge( $returnArray[$submetaFieldName], array( ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) ) );
                        }
                        else
                        {
                            $returnArray[$submetaFieldName] = array( ezfSolrDocumentFieldBase::preProcessValue( $metaInfo['value'], $metaInfo['fieldType'] ) );
                        }
                    }
                }
                $defaultFieldName = parent::generateAttributeFieldName( $contentClassAttribute,
                                                                        self::$subattributesDefinition[self::DEFAULT_SUBATTRIBUTE] );
                $returnArray[$defaultFieldName] = $this->strip_html_tags( $this->getPlainTextRepresentation( ) );
                return $returnArray;
            } break;
         
            default:
            {
                    $return = array( $fieldName => '' );
            } break;
        }
        return $returm;
    }
}
 

?>
