<?php
/**
 * The vendor exception
 * Thrown when a vendor THROWS an exception
 * But it's not a warning, it's an error.
 * Ex: the template system fails and throws an exception
 * Then the message is catched and sent to this one.
 * OR
 * The TwitterOAuth lib throws an exception
 * It's also catches and sent to this one
 *
 * @author Zerquix <malbertoa_11@hotmail.com>
 * @copyright  Luis A. Martínez 2016
 */

class VendorException extends \Exception
{
    private $vendor;
    public function __construct($vendor, $message)
    {
        $this->vendor = $vendor;
        parent::__construct($message);
    }
    public function getVendor()
    {
        return $this->vendor;
    }
}