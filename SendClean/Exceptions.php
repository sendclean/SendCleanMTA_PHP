<?php

class SendClean_Error extends Exception {}
class SendClean_HttpError extends SendClean_Error {}

/**
 * The parameters passed to the API call are invalid or not provided when required
 */
class SendClean_ValidationError extends SendClean_Error {}

/**
 * The provided API key is not a valid SendClean API key
 */
class SendClean_Invalid_Key extends SendClean_Error {}

