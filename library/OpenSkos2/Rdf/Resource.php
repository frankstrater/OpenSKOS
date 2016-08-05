<?php

/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace OpenSkos2\Rdf;


use OpenSkos2\Exception\OpenSkosException;
use OpenSkos2\Exception\UriGenerationException;
use OpenSkos2\MyInstitutionModules\UriGeneration;
use OpenSkos2\Namespaces as Namespaces;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Object as RdfObject;
use OpenSkos2\Rdf\Uri;

class Resource extends Uri implements ResourceIdentifier
{
    /**
     * openskos:status value which marks a resource as deleted.
     */
    const STATUS_DELETED = 'deleted';
    
    protected $properties = [];

    /**
     * @return array of RdfObject[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $predicate
     * @return RdfObject[]
     */
    public function getProperty($predicate)
    {
        if (!isset($this->properties[$predicate])) {
            return [];
        } else {
            return $this->properties[$predicate];
        }
    }
    
    // used in Tenant and Set
    public function getCode() {
        return $this ->getPropertyOneLiteralValue(OpenSkos::CODE);
    }
    
    // used in Skos:collection and ConceptScheme 
    // (For sets one may also need to check MySQL, 
    // so that's why they are implemented separately)
    public function getTitle() {
        return $this ->getPropertyOneLiteralValue(DcTerms::TITLE);
    }
    
    private function getPropertyOneLiteralValue($propertyURI){
       $values = $this->getProperty($propertyURI);
        if (isset($values[0])) {
            return $values[0];
        }else{
            return new Literal(UNKNOWN);
        } 
    }
    
    /**
     * @param string $predicate
     * @param RdfObject $value
     */
    public function addProperty($predicate, RdfObject $value)
    {
        $this->properties[$predicate][] = $value;
        return $this;
    }
    
    /**
     * Make sure the property is only added once
     *
     * @param string $predicate
     * @param RdfObject $value
     * @return Resource
     */
    public function addUniqueProperty($predicate, RdfObject $value)
    {
        if (!isset($this->properties[$predicate])) {
            $this->properties[$predicate][] = $value;
            return $this;
        }
        foreach ($this->properties[$predicate] as $obj) {
            if ($obj instanceof Literal && $value instanceof Literal) {
                if ($obj->getValue() === $value->getValue() && $obj->getLanguage() === $value->getLanguage()) {
                    return $this;
                }
            } elseif ($obj instanceof Uri && $value instanceof Uri) {
                if ($obj->getUri() === $value->getUri()) {
                    return $this;
                }
            }
        }
        $this->properties[$predicate][] = $value;
        return $this;
    }

    /**
     * @param string $predicate
     * @param RdfObject $value
     * @return $this
     */
    public function setProperty($predicate, RdfObject $value)
    {
        $this->properties[$predicate] = [$value];
        return $this;
    }
    
    /**
     * Set multiple values at once, override existing values
     *
     * @param string $predicate
     * @param RdfObject[] $values
     */
    public function setProperties($predicate, array $values)
    {
        $this->properties[$predicate] = $values;
    }

    /**
     * @param string $predicate
     */
    public function unsetProperty($predicate)
    {
        unset($this->properties[$predicate]);
        return $this;
    }

    /**
     * @param string $predicate
     * @return bool
     */
    public function hasProperty($predicate)
    {
        return isset($this->properties[$predicate]);
    }
    
    /**
     * @param string $predicate
     * @return bool
     */
    public function isPropertyEmpty($predicate)
    {
        return empty($this->properties[$predicate]);
    }
    
    /**
     * @param string $predicate
     * @return bool
     */
    public function isPropertyTrue($predicate)
    {
        if (!$this->isPropertyEmpty($predicate)) {
            $values = $this->getProperty($predicate);
            return (bool) $values[0]->getValue();
        }
        return false;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return current($this->getProperty(Rdf::TYPE));
    }
    
    public function getCreator()
    {   
        if ($this->hasProperty(DcTerms::CREATOR)) {
            $tmp = current($this->getProperty(DcTerms::CREATOR));
            if ($tmp instanceof Literal) { // literal value for UNKNOWN
                return $tmp->getValue();
            } else {
                if ($tmp instanceof Uri) {
                    return $tmp->getUri();
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }
    }
    
    public function getDateSubmitted()
    {
        return current($this->getProperty(DcTerms::DATESUBMITTED));
    }
    
    public function getUuid()
    {
        return $this->getPropertyOneLiteralValue(OpenSkos::UUID);
    }
    /**
     * @return string
     */
    public function getCaption($language = null)
    {
        return $this->uri;
    }
    
    /**
     * @return string|null
     */
    public function getStatus()
    {
        if (!$this->hasProperty(OpenSkos::STATUS)) {
            return null;
        } else {
            return $this->getProperty(OpenSkos::STATUS)[0]->getValue();
        }
    }
    
    /**
     * Is the current resource a blank node.
     * It is if no uri given or generated uri starting with _:
     * @return boolean
     */
    public function isBlankNode()
    {
        return empty($this->uri) || preg_match('/^_:/', $this->uri);
    }
    
    /**
     * Go through the propery values and check if there is one in the specified language.
     * @param string $predicate
     * @param string $language
     * @return bool
     */
    public function hasPropertyInLanguage($predicate, $language)
    {
        foreach ($this->getProperty($predicate) as $value) {
            if ($value instanceof Literal && $value->getLanguage() == $language) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Gets the specified property values but filter only those in the specified language.
     * @TODO Rename to getPropertyInLanguage
     * @param string $predicate
     * @param string $language
     * @return RdfObject[]
     */
    public function retrievePropertyInLanguage($predicate, $language)
    {
        $values = [];
        foreach ($this->getProperty($predicate) as $value) {
            if ($value instanceof Literal && $value->getLanguage() === $language) {
                $values[] = $value;
            }
        }
        return $values;
    }
    
    /**
     * Gets list of all languages that currently exist in the properties of the resource.
     * @TODO Rename to getLanguages
     * @return string[]
     */
    public function retrieveLanguages()
    {
        $languages = [];
        foreach ($this->getProperties() as $property) {
            foreach ($property as $value) {
                if ($value instanceof Literal
                        && $value->getLanguage() !== null
                        && !isset($languages[$value->getLanguage()])) {
                    $languages[$value->getLanguage()] = true;
                }
            }
        }
        
        return array_keys($languages);
    }
    
    /**
     * Gets proprty value and checks if it is only one.
     * @param string $property
     * @return string|null
     */
    public function getPropertySingleValue($property)
    {
        $values = $this->getProperty($property);
        
        if (count($values) > 1) {
            throw new OpenSkosException(
                'Multiple values found for property "' . $property . '" while a single one was requested.'
                . ' Values ' . implode(', ', $values)
            );
        }
        
        if (!empty($values)) {
            return $values[0];
        } else {
            return null;
        }
    }
    
    /**
     * Gets proprty value and implodes it if multiple values are found.
     * @param string $property
     * @param string $language
     * @return string
     */
    public function getPropertyFlatValue($property, $language = null)
    {
        if (!empty($language)) {
            $values = $this->retrievePropertyInLanguage($property, $language);
        } else {
            $values = $this->getProperty($property);
        }
        
        return implode(', ', $values);
    }
    
    /**
     * Gets the resource in simple flat array with all (or filtered) properties.
     * @param array $filter , optional
     * @param string $language , optional
     * @return array
     */
    public function toFlatArray($filter = [], $language = null)
    {
        $result = [];
        
        foreach (array_keys($this->getProperties()) as $property) {
            if (empty($filter) || in_array($property, $filter)) {
                $result[Namespaces::shortenProperty($property)] = $this->getPropertyFlatValue($property, $language);
            }
        }
        
        // @TODO uri and caption are out of scope here, but really handful.
        if (empty($filter) || in_array('uri', $filter)) {
            $result['uri'] = $this->getUri();
        }
        if (empty($filter) || in_array('caption', $filter)) {
            $result['caption'] = $this->getCaption($language);
        }
        
        return $result;
    }
    
    // override for a concerete resources if necessary
    public function addMetadata($userUri, $params, $existingResource) {
        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };
        
        if ($existingResource === null) { // a completely new resource under creation
            $this->setProperty(DcTerms::CREATOR, new Uri($userUri));
            $this->setProperty(DcTerms::DATESUBMITTED, $nowLiteral());
        } else {
            $this->setProperty(DcTerms::MODIFIED, $nowLiteral());
            $this->addProperty(DcTerms::CONTRIBUTOR, new Uri($userUri));
            $this->setProperty(OpenSkos::UUID, $existingResource->getUuid());
            $creators = $existingResource->getProperty(DcTerms::CREATOR);
            if (count($creators) === 0) {
                $this->setProperty(DcTerms::CREATOR, new Literal(UNKNOWN));
            } else {
                $this->setProperty(DcTerms::CREATOR, $creators[0]);
            }
            $dateSubmitted = $existingResource->getProperty(DcTerms::DATESUBMITTED);
            if (count($dateSubmitted) !== 0) {
                $this->setProperty(DcTerms::DATESUBMITTED, new Literal($dateSubmitted[0], null, Literal::TYPE_DATETIME));
            }
        }
    }
    
    //parameters can include e.g. tenantcode, seturi
    public function selfGenerateUuidAndUriWhenAbsent(ResourceManager $manager, array $parameters) {

        $uuids = $this->getProperty(OpenSkos::UUID);
        if (count($uuids) < 1) {
            if (!isset($parameters['uuid'])) {
                $parameters['uuid'] = UriGeneration::generateUUID($parameters);
            }
            $this->setProperty(OpenSkos::UUID, new Literal($parameters['uuid']));
        } else {
            $parameters['uuid'] = $uuids[0]->getValue();
        }

        if ($this->isBlankNode()) {
            $uri = UriGeneration::generateURI($parameters);
            if ($manager->askForUri($uri, true)) {
                throw new UriGenerationException(
                'The generated uri "' . $uri . '" is already in use.'
                );
            }
             $this->setUri($uri);
            return $uri;
        } else {
            return $this->getUri();
        }
    }
    
    protected function deriveSetUri($params, $existingResource) {
        if (count($this->getProperty(OpenSkos::SET)) < 1) {
            if ($existingResource != null) {
                $sets = $existingResource->getProperty(OpenSkos::SET);
                if (count($sets) < 1) {
                    if ($params['seturi'] !== null) {
                        return new Uri($params['seturi']);
                    }
                } else {
                    return $sets[0];
                }
            } else {
                if ($params['seturi'] !== null) {
                    return new Uri($params['seturi']);
                }
            }
        }
        return null;
    }
}
