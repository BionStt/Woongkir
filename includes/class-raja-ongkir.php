<?php
/**
 * The file that defines the Raja_Ongkir class
 *
 * @link       https://github.com/sofyansitorus
 * @since      1.0.0
 *
 * @package    Woongkir
 * @subpackage Woongkir/includes
 */

/**
 * The Raja_Ongkir API class.
 *
 * This is used to make request to RajaOngkir.com API server.
 *
 * @since      1.0.0
 * @package    Woongkir
 * @subpackage Woongkir/includes
 * @author     Sofyan Sitorus <sofyansitorus@gmail.com>
 */
class Raja_Ongkir {

	/**
	 * Class options.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $options = array();

	/**
	 * List of account type and allowed features.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $accounts = array(
		'starter' => array(
			'label'       => 'Starter',
			'api_url'     => 'http://api.rajaongkir.com/starter',
			'subdistrict' => false,
			'multiple'    => false,
		),
		'basic'   => array(
			'label'       => 'Basic',
			'api_url'     => 'http://api.rajaongkir.com/basic',
			'subdistrict' => true,
			'multiple'    => true,
		),
		'pro'     => array(
			'label'       => 'Pro',
			'api_url'     => 'http://pro.rajaongkir.com/api',
			'subdistrict' => true,
			'multiple'    => true,
		),
	);

	/**
	 * List of used delivery couriers and services.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $couriers = array(
		'domestic'      => array(
			'jne'  => array(
				'label'    => 'Jalur Nugraha Ekakurir',
				'services' => array(
					'CTC',
					'CTCYES',
					'OKE',
					'REG',
					'YES',
				),
				'account'  => array(
					'starter',
					'basic',
					'pro',
				),
				'multiple' => array(
					'basic',
					'pro',
				),
			),
			'tiki' => array(
				'label'    => 'Citra Van Titipan Kilat',
				'services' => array(
					'TRC',
					'REG',
					'ECO',
					'ONS',
					'SDS',
					'HDS',
				),
				'account'  => array(
					'starter',
					'basic',
					'pro',
				),
				'multiple' => array(
					'basic',
					'pro',
				),
			),
			'pos'  => array(
				'label'    => 'POS Indonesia',
				'services' => array(
					'Surat Kilat Khusus',
					'Paketpos Biasa',
					'Paket Kilat Khusus',
					'Express Sameday Dokumen',
					'Express Sameday Barang',
					'Express Next Day Dokumen',
					'Express Next Day Barang',
					'Paketpos Dangerous Goods',
					'Paketpos Valuable Goods',
				),
				'account'  => array(
					'starter',
					'basic',
					'pro',
				),
				'multiple' => array(
					'basic',
					'pro',
				),
			),
			'pcp'  => array(
				'label'    => 'Priority Cargo and Package',
				'services' => array(
					'ONS',
					'NFS',
					'REG',
				),
				'account'  => array(
					'basic',
					'pro',
				),
				'multiple' => array(
					'basic',
					'pro',
				),
			),
			'rpx'  => array(
				'label'    => 'RPX Holding',
				'services' => array(
					'SDP',
					'MDP',
					'NDP',
					'RGP',
					'REP',
					'ERP',
				),
				'account'  => array(
					'basic',
					'pro',
				),
				'multiple' => array(
					'basic',
					'pro',
				),
			),
		),
		'international' => array(
			'jne' => array(
				'label'    => 'Jalur Nugraha Ekakurir',
				'services' => array(
					'INTL',
				),
				'account'  => array(
					'basic',
					'pro',
				),
				'multiple' => array(
					'basic',
					'pro',
				),
			),
			'pos' => array(
				'label'    => 'POS Indonesia',
				'services' => array(
					'Surat R LN',
					'EMS BARANG',
					'PAKETPOS CEPAT LN',
					'PAKETPOS BIASA LN',
				),
				'account'  => array(
					'basic',
					'pro',
				),
				'multiple' => array(
					'basic',
					'pro',
				),
			),
		),
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param array $options Class options.
	 */
	public function __construct( $options = array() ) {
		if ( $options && is_array( $options ) ) {
			foreach ( $options as $key => $value ) {
				$this->set_option( $key, $value );
			}
		}
	}

	/**
	 * Get shipping cost.
	 *
	 * @since 1.0.0
	 * @param array $destination Shipping destination data.
	 * @param array $origin Shipping origin data.
	 * @param array $dimension_weight Shipping package weight and dimension data.
	 * @param array $courier Request Shipping couriers data.
	 * @return array
	 */
	public function get_cost( $destination, $origin, $dimension_weight, $courier ) {
		$account  = $this->get_account( $this->get_option( 'account_type' ) );
		$endpoint = empty( $destination['country'] ) ? 'cost' : 'internationalCost';

		switch ( $endpoint ) {
			case 'internationalCost':
				$params = array(
					'destination' => $destination['country'],
					'origin'      => $origin['city'],
					'courier'     => ( $account['multiple'] ) ? implode( ':', $courier ) : $courier[0],
				);
				break;

			default:
				$params = array(
					'destination'     => ( $account['subdistrict'] && ! empty( $destination['subdistrict'] ) ) ? $destination['subdistrict'] : $destination['city'],
					'destinationType' => ( $account['subdistrict'] && ! empty( $destination['subdistrict'] ) ) ? 'subdistrict' : 'city',
					'origin'          => ( $account['subdistrict'] && ! empty( $origin['subdistrict'] ) ) ? $origin['subdistrict'] : $origin['city'],
					'originType'      => ( $account['subdistrict'] && ! empty( $origin['subdistrict'] ) ) ? 'subdistrict' : 'city',
					'courier'         => ( $account['multiple'] ) ? implode( ':', $courier ) : $courier[0],
				);
				break;
		}

		$params = array_merge( $params, $dimension_weight );
		return $this->remote_post( $endpoint, $params );
	}

	/**
	 * Get currency exchange value.
	 *
	 * @since 1.0.0
	 */
	public function get_currency() {
		return $this->remote_get( 'currency' );
	}

	/**
	 * Get account data.
	 *
	 * @since 1.0.0
	 * @param string $account_type Acoount type key.
	 */
	public function get_account( $account_type = null ) {
		if ( ! is_null( $account_type ) ) {
			return isset( $this->accounts[ $account_type ] ) ? $this->accounts[ $account_type ] : false;
		}
		return $this->accounts;
	}

	/**
	 * Get courier data.
	 *
	 * @since 1.0.0
	 * @param string $zone_id Courier key.
	 */
	public function get_courier( $zone_id = null ) {
		if ( ! is_null( $zone_id ) ) {
			return isset( $this->couriers[ $zone_id ] ) ? $this->couriers[ $zone_id ] : false;
		}
		return $this->couriers;
	}

	/**
	 * Set class option.
	 *
	 * @since 1.0.0
	 * @param string $key Option key.
	 * @param mixed  $value Option value.
	 */
	public function set_option( $key, $value ) {
		$this->options[ $key ] = $value;
		return $this;
	}

	/**
	 * Get class option.
	 *
	 * @since 1.0.0
	 * @param string $key Option key.
	 * @param string $default Option default value.
	 */
	public function get_option( $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	/**
	 * Make request to API server.
	 *
	 * @since 1.0.0
	 * @param string $endpoint API request URL endpoint.
	 * @param array  $params API request parameters.
	 */
	public function remote_request( $endpoint, $params = array() ) {

		$args = wp_parse_args(
			$params,
			array(
				'headers' => array(
					'key' => $this->get_option( 'api_key' ),
				),
			)
		);

		$response = wp_remote_request( $this->url( $endpoint ), $args );

		return $this->validate_api_response( $response );

	}

	/**
	 * Make request to API server using the POST method.
	 *
	 * @since 1.0.0
	 * @param string $endpoint API request URL endpoint.
	 * @param array  $body API request body parameters.
	 */
	public function remote_post( $endpoint, $body = array() ) {

		$args = array(
			'headers' => array(
				'key'          => $this->get_option( 'api_key' ),
				'content-type' => 'application/x-www-form-urlencoded',
			),
			'body'    => $body,
		);

		$response = wp_remote_post( $this->url( $endpoint ), $args );

		return $this->validate_api_response( $response );

	}

	/**
	 * Make request to API server using the GET method.
	 *
	 * @since 1.0.0
	 * @param string $endpoint API request URL endpoint.
	 * @param array  $query_url API request URL query string parameters.
	 */
	public function remote_get( $endpoint, $query_url = array() ) {

		$args = array(
			'headers' => array(
				'key' => $this->get_option( 'api_key' ),
			),
		);

		$url = $this->url( $endpoint );

		if ( $query_url ) {
			$url = add_query_arg( $query_url, $url );
		}

		$response = wp_remote_get( $url, $args );

		return $this->validate_api_response( $response );

	}

	/**
	 * Validate API request response.
	 *
	 * @since 1.0.0
	 * @param mixed $response API request response data.
	 */
	private function validate_api_response( $response ) {

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new WP_Error( 'api_response_empty', __( 'API response is empty.', 'woongkir' ) );
		}

		$data = json_decode( $body );

		if ( json_last_error() !== JSON_ERROR_NONE || ! $data ) {
			return new WP_Error( 'api_response_invalid', __( 'API response is invalid.', 'woongkir' ) );
		}

		if ( isset( $data->rajaongkir->status ) && 200 !== $data->rajaongkir->status->code ) {
			return new WP_Error( 'api_response_error_' . $data->rajaongkir->status->code, $data->rajaongkir->status->description );
		}

		if ( isset( $data->rajaongkir->results ) ) {
			return $data->rajaongkir->results;
		}

		if ( isset( $data->rajaongkir->result ) ) {
			return $data->rajaongkir->result;
		}

		return new WP_Error( 'unknown_error', __( 'Unknown error', 'woongkir' ) );
	}

	/**
	 * Get API request URL.
	 *
	 * @since 1.0.0
	 * @param string $endpoint API URL endpoint.
	 * @return string
	 */
	private function url( $endpoint ) {
		$account = $this->get_account( $this->get_option( 'account_type' ) );
		switch ( $endpoint ) {
			case 'internationalOrigin':
			case 'internationalDestination':
			case 'internationalCost':
				$url = $account['api_url'] . '/v2/' . $endpoint;
				break;

			default:
				$url = $account['api_url'] . '/' . $endpoint;
				break;
		}
		return $url;
	}
}