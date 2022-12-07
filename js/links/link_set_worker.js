let LinkSetWorker = new function(){

	// defines
	const ROUTER_BASE_URL = '../pages/ajax.render.php';
	const ROUTE_LINK_SET_DELETE_OBJECT = 'linkset.DeleteLinkedObject';
	const ROUTE_LINK_SET_DETACH_OBJECT = 'linkset.DetachLinkedObject';

	/**
	 * CallAjaxDeleteLinkedObject.
	 *
	 * @param sLinkedObjectClass
	 * @param sLinkedObjectKey
	 * @constructor
	 */
	const CallAjaxDeleteLinkedObject = function(sLinkedObjectClass, sLinkedObjectKey, callback){
		$.post(`${ROUTER_BASE_URL}?route=${ROUTE_LINK_SET_DELETE_OBJECT}`, {
			linked_object_class: sLinkedObjectClass,
			linked_object_key: sLinkedObjectKey,
			transaction_id: $('#linkset_transactions_id').val()
		}, callback);
	};

	/**
	 * CallAjaxDetachLinkedObject.
	 *
	 * @param sLinkedObjectClass
	 * @param sLinkedObjectKey
	 * @param sExternalKeyAttCode
	 * @constructor
	 */
	const CallAjaxDetachLinkedObject = function(sLinkedObjectClass, sLinkedObjectKey, sExternalKeyAttCode, callback){
		$.post(`${ROUTER_BASE_URL}?route=${ROUTE_LINK_SET_DETACH_OBJECT}`, {
			linked_object_class: sLinkedObjectClass,
			linked_object_key: sLinkedObjectKey,
			external_key_att_code: sExternalKeyAttCode,
			transaction_id: $('#linkset_transactions_id').val()
		}, callback);
	};

	return {
		DeleteLinkedObject: CallAjaxDeleteLinkedObject,
		DetachLinkedObject: CallAjaxDetachLinkedObject
	}
};