<?php 

namespace Client;

/**
 * @author ccollier
 */
interface ContentDelegator {
    
    /**
     * @param ContentDelegate $Delegate
     */
    function setDelegate( ContentDelegate $Delegate );
}