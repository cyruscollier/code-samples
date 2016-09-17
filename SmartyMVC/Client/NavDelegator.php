<?php 

namespace Client;

/**
 * @author ccollier
 */
interface NavDelegator {
    
    /**
     * @param NavDelegate $Delegate
     */
    function setDelegate( NavDelegate $Delegate );
    
}