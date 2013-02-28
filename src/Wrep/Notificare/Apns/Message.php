<?php

namespace Wrep\Notificare\Apns;

class Message
{
	private $deviceToken;
	private $certificate;
	private $expiresAt;

	private $alert;
	private $badge;
	private $sound;
	private $payload;

	/**
	 * Construct Message
	 *
	 * @param $deviceToken string Receiver of this message
	 * @param $certificate Certificate The certificate that must be used for the APNS connection this message is send over
	 */
	public function __construct($deviceToken, Certificate $certificate = null)
	{
		// Check if a devicetoken is given
		if (null == $deviceToken) {
			throw new \InvalidArgumentException('No device token given.');
		}

		// Check if the devicetoken is a valid hexadecimal string
		if (!ctype_xdigit($deviceToken)) {
			throw new \InvalidArgumentException('Invalid device token given, no hexadecimal: ' . $deviceToken);
		}

		// Check if the length of the devicetoken is correct
		if (64 != strlen($deviceToken)) {
			throw new \InvalidArgumentException('Invalid device token given, incorrect length: ' . $deviceToken . ' (' . strlen($deviceToken) . ')');
		}

		// Set the devicetoken
		$this->deviceToken = $deviceToken;
		$this->certificate = $certificate;
		$this->expiresAt = 0;

		// Set the defaults
		$this->alert = null;
		$this->badge = null;
		$this->sound = null;
		$this->payload = null;
	}

	/**
	 * Get the device token of the receiving device
	 *
	 * @return string
	 */
	public function getDeviceToken()
	{
		return $this->deviceToken;
	}

	/**
	 * Get the certificate that should be used for this message
	 *
	 * @return Certificate
	 */
	public function getCertificate()
	{
		return $this->certificate;
	}

	/**
	 * Get the moment this message expires
	 *
	 * @return int Unix timestamp of expiry moment or zero if no specific expiry moment is set
	 */
	public function getExpiresAt()
	{
		return $this->expiresAt;
	}

	/**
	 * Set the moment this message should expire or null if APNS should not store the message at all
	 *  The last message for a device is stored at APNS for delivery until this moment if the device is offline
	 *
	 * @param $expiresAt DateTime|null Date until the message should be stored for delivery
	 */
	public function setExpiresAt(\DateTime $expiresAt = null)
	{
		$this->expiresAt = (null == $expiresAt) ? 0 : $expiresAt->format('U');
	}

	/**
	 * Set the alert to display
	 *  Also see: http://developer.apple.com/library/ios/#documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/ApplePushService/ApplePushService.html#//apple_ref/doc/uid/TP40008194-CH100-SW1
	 *
	 * @param $body string|null The text of the alert to display or null to set no alert
	 * @param $actionLocKey string|null The localization key to use for the action button
	 * @param $launchImage string|null The name of the launch image to use
	 */
	public function setAlert($body, $actionLocKey = null, $launchImage = null)
	{
		// Check if a boday is given
		if (null == $body && (null !== $actionLocKey || null !== $launchImage)) {
			throw new \InvalidArgumentException('No alert body given, but action-loc-key and/or launch-image given.');
		}

		// Check if we must use an JSON object
		if (null == $actionLocKey && null == $launchImage)
		{
			// No, just use a string
			$this->alert = $body;
		}
		else
		{
			// Yes, use an object
			$this->alert = array('body' => $body);

			if ($actionLocKey) {
				$this->alert['action-loc-key'] = $actionLocKey;
			}

			if ($launchImage) {
				$this->alert['launch-image'] = $launchImage;
			}
		}
	}

	/**
	 * Set the localized alert to display
	 *  Also see: http://developer.apple.com/library/ios/#documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/ApplePushService/ApplePushService.html#//apple_ref/doc/uid/TP40008194-CH100-SW1
	 *
	 * @param $locKey string The localization key to use for the text of the alert
	 * @param $locArgs array The arguments that fill the gaps in the locKey text
	 * @param $actionLocKey string|null The localization key to use for the action button
	 * @param $launchImage string|null The name of the launch image to use
	 */
	public function setAlertLocalized($locKey, $locArgs = array(), $actionLocKey = null, $launchImage = null)
	{
		// Check if a locKey is given
		if (null == $locKey) {
			throw new \InvalidArgumentException('No alert locKey given.');
		}

		// Check if a locArgs is an array
		if (!is_array($locArgs)) {
			throw new \InvalidArgumentException('No alert locArgs given.');
		}

		// Set the alert
		$this->alert = array('loc-key' => $locKey,  'loc-args' => $locArgs);

		if ($actionLocKey) {
			$this->alert['action-loc-key'] = $actionLocKey;
		}

		if ($launchImage) {
			$this->alert['launch-image'] = $launchImage;
		}
	}

	/**
	 * Get the current alert
	 *
	 * @return string|array
	 */
	public function getAlert()
	{
		return $this->alert;
	}

	/**
	 * Set the badge to display on the App icon
	 *
	 * @param $badge int|null
	 */
	public function setBadge($badge)
	{
		// Validate the badge int
		if ((int)$badge < 0) {
			throw new \OutOfBoundsException('Badge must be 0 or higher.');
		}

		// Cast to int or set to null
		$this->badge = (null === $badge) ? null : (int)$badge;
	}

	/**
	 * Clear the badge from the App icon
	 */
	public function clearBadge()
	{
		$this->setBadge(0);
	}

	/**
	 * Get the value of the badge as set in this message
	 *
	 * @return int|null
	 */
	public function getBadge()
	{
		return $this->badge;
	}

	/**
	 * Set the sound that will be played when this message is received
	 *
	 * @param $sound string Optional string of the sound to play, no string will play the default sound
	 */
	public function setSound($sound = 'default')
	{
		$this->sound = $sound;
	}

	/**
	 * Get the sound that will be played when this message is received
	 *
	 * @param $sound string|null
	 */
	public function getSound()
	{
		return $this->sound;
	}

	/**
	 * Set custom payload to go with the message
	 *
	 * @param $payload array|json|null The payload to send as array or JSON string
	 */
	public function setPayload($payload)
	{
		if ( (is_string($payload) && empty($payload)) || (is_array($payload) && count($payload) == 0) )
		{
			// Empty strings or arrays are not allowed
			throw new \InvalidArgumentException('Invalid payload for message. Payload was empty, but not null)');
		}
		else if (is_array($payload) || null === $payload)
		{
			// This is okay, set as payload
			$this->payload = $payload;
		}
		else
		{
			// Try to decode JSON string payload
			$payload = json_decode($payload, true);

			// Check if decoding the payload worked
			if (null === $payload) {
				throw new \InvalidArgumentException('Invalid payload for message. Payload was invalid JSON.');
			}

			// Set as payload
			$this->payload = $payload;
		}
	}

	/**
	 * Get the current payload
	 *
	 * @return array|null
	 */
	public function getPayload()
	{
		return $this->payload;
	}

	/**
	 * Get the JSON payload that should be send to the APNS
	 *
	 * @return string
	 */
	public function getJson()
	{
		// Get message array to create JSON from
		$message = array();

		// If we have a payload replace the message object by the payload
		if (null !== $this->payload) {
			$message = $this->payload;
		}

		// Add the alert if any
		if (null !== $this->alert) {
			$message['alert'] = $this->alert;
		}

		// Add the badge if any
		if (null !== $this->badge) {
			$message['badge'] = $this->badge;
		}

		// Add the sound if any
		if (null !== $this->sound) {
			$message['sound'] = $this->sound;
		}

		// Encode as JSON object
		return json_encode($message, JSON_FORCE_OBJECT);
	}
}