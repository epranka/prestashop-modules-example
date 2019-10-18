class CustomCheckout {
	constructor() {
		this.requestTimeout = 15 * 1000;
		this.lockLoading = false;
		this.updateShippingAddressRequest = null;

		this.handleOmnivaAddressChange = this.handleOmnivaAddressChange.bind(
			this
		);
		this.handleLpExpressAddressChange = this.handleLpExpressAddressChange.bind(
			this
		);
		this.handleInvoiceCheckboxChange = this.handleInvoiceCheckboxChange.bind(
			this
		);
		this.handleTosChange = this.handleTosChange.bind(this);
		this.handleFormChange = this._debounce(
			this.handleFormChange.bind(this),
			300
		);

		// this.handleMobilePaste = this.handleMobilePaste.bind(this);

		// this.updateShippingAddress = this._debounce(
		//     this.updateShippingAddress.bind(this),
		//     300
		// );

		this.init = this.init.bind(this);

		$(document).ready(this.init);
	}

	bindEvents() {
		$("body").undelegate(
			'.opc-main-block select[name="omnivalt_parcel_terminal"]',
			"change",
			this.handleOmnivaAddressChange
		);
		$("body").delegate(
			'.opc-main-block select[name="omnivalt_parcel_terminal"]',
			"change",
			this.handleOmnivaAddressChange
		);

		$("body").undelegate(
			'.opc-main-block select[name="lp_terminal_machineid"]',
			"change",
			this.handleLpExpressAddressChange
		);
		$("body").delegate(
			'.opc-main-block select[name="lp_terminal_machineid"]',
			"change",
			this.handleLpExpressAddressChange
		);

		$("body").undelegate(
			"input[type=checkbox][name=need_invoice]",
			"change",
			this.handleInvoiceCheckboxChange
		);
		$("body").delegate(
			"input[type=checkbox][name=need_invoice]",
			"change",
			this.handleInvoiceCheckboxChange
		);

		$("body").undelegate(
			"#customCheckoutForm",
			"change",
			this.handleFormChange
		);
		$("body").delegate(
			"#customCheckoutForm",
			"change",
			this.handleFormChange
		);
		// Term Of Service (TOS)
		$("body").undelegate("#cgv", "click", this.handleTosChange);
		$("body").delegate("#cgv", "click", this.handleTosChange);

		// $("body").undelegate(
		// 	"input[name=phone_mobile]",
		// 	"paste",
		// 	this.handleMobilePaste
		// );
		// $("body").delegate(
		// 	"input[name=phone_mobile]",
		// 	"paste",
		// 	this.handleMobilePaste
		// );

		// $(document.body).on(
		// 	"paste",
		// 	"input#phone_mobile",
		// 	this.handleMobilePaste
		// );
	}

	isNeedInvoice() {
		return !!$("input[type=checkbox][name=need_invoice]").attr("checked");
	}

	getCheckoutForm() {
		return $("#customCheckoutForm");
	}

	// handleMobilePaste(e) {
	// 	e.preventDefault();
	// 	let pasteData = e.originalEvent.clipboardData.getData("text/plain");
	// 	pasteData = pasteData.replace(/\s+/g, "");
	// 	$(e.target).val(pasteData);
	// 	this.manageFormChange();
	// }

	async init() {
		this.startCheckoutLoading();
		this.bindEvents();
		this.lockLoading = true;
		await this.updateCarrierSelection();
		this.manageInvoiceCheckbox(this.isNeedInvoice());
		await this.manageFormChange();
		this.lockLoading = false;
		this.stopCheckoutLoading();
	}

	handleOmnivaAddressChange(e) {
		this.updateOmnivaAddress(e.target.value);
	}

	handleLpExpressAddressChange(e) {
		this.updateLpExpressAddress(e.target.value);
	}

	handleInvoiceCheckboxChange(e) {
		e.preventDefault();
		this.manageInvoiceCheckbox(e.target.checked);
	}

	handleFormChange(e) {
		this.manageFormChange();
	}

	async handleTosChange() {
		this.startCheckoutLoading();
		this.lockLoading = true;
		await this.manageFormChange();
		await this.updatePaymentMethodsDisplay();
		this.lockLoading = false;
		this.stopCheckoutLoading();
	}

	async manageFormChange() {
		var $form = this.getCheckoutForm();
		var values = {};
		$.each($form.serializeArray(), function(i, field) {
			values[field.name] = field.value;
		});
		this.startCheckoutLoading();
		await this.updateShippingAddress(values);
		this.stopCheckoutLoading();
	}

	updatePaymentMethodsDisplay() {
		return new Promise(resolve => {
			updatePaymentMethodsDisplay(resolve);
		});
	}

	updateOmnivaAddress(terminalId) {
		return new Promise((resolve, reject) => {
			console.log("updating omniva", terminalId);
			this.startCheckoutLoading();
			$.ajax({
				dataType: "json",
				type: "POST",
				url:
					baseDir +
					"modules/kodminacustomcheckout/ajax/setOmnivaAddress.php",
				data: {
					terminalId: terminalId
				},
				timeout: this.requestTimeout
			})
				.done(data => {
					if (data.success) {
						this.updateCarrierForm(data);
					}
					updatePaymentMethodsDisplay(resolve); // todo this with promise
					this.stopCheckoutLoading();
				})
				.fail((jqXHR, textStatus) => {
					this.handleError(jqXHR, textStatus);
					console.log(jqXHR);
					console.log(textStatus);
					// this.stopCheckoutLoading();
					reject();
				});
		});
	}

	updateLpExpressAddress(machineId) {
		return new Promise((resolve, reject) => {
			console.log("updating lpexpress", machineId);
			this.startCheckoutLoading();
			$.ajax({
				dataType: "json",
				type: "POST",
				url:
					baseDir +
					"modules/kodminacustomcheckout/ajax/setLpExpressAddress.php",
				data: {
					machineId: machineId
				},
				timeout: this.requestTimeout
			})
				.done(data => {
					if (data.success) {
						this.updateCarrierForm(data);
					}
					updatePaymentMethodsDisplay(resolve); // todo this with promise
					this.stopCheckoutLoading();
				})
				.fail((jqXHR, textStatus) => {
					this.handleError(jqXHR, textStatus);
					console.log(jqXHR);
					console.log(textStatus);
					reject();
				});
		});
	}

	updateManualAddress() {
		return new Promise((resolve, reject) => {
			console.log("updating manual address");
			this.startCheckoutLoading();
			$.ajax({
				dataType: "json",
				type: "POST",
				url:
					baseDir +
					"/modules/kodminacustomcheckout/ajax/setManualAddress.php",
				timeout: this.requestTimeout
			})
				.done(data => {
					if (data.success) {
						this.updateCarrierForm(data);
					}
					updatePaymentMethodsDisplay(resolve); // todo this with promise
					this.stopCheckoutLoading();
				})
				.fail((jqXHR, textStatus) => {
					this.handleError(jqXHR, textStatus);
					console.log(jqXHR);
					console.log(textStatus);
					reject();
				});
		});
	}

	updateShippingAddress(values) {
		return new Promise((resolve, reject) => {
			console.log("updating shipping address");
			if (this.updateShippingAddressRequest) {
				this.updateShippingAddressRequest.abort();
			}
			this.updateShippingAddressRequest = $.ajax({
				dataType: "json",
				type: "POST",
				url:
					baseDir +
					"modules/kodminacustomcheckout/ajax/updateShippingAddress.php",
				data: values,
				timeout: this.requestTimeout
			})
				.done(data => {
					if (data && data.hasErrors) {
						// delivery address errors
						var tmp = "";
						var i = 0;
						for (var error in data.errors)
							if (
								error !== "indexOf" &&
								!error.endsWith("_invoice")
							) {
								i = i + 1;
								tmp += "<li>" + data.errors[error] + "</li>";
							}
						tmp += "</ol>";
						var errors =
							"<b>" +
							txtThereis +
							" " +
							i +
							" " +
							txtErrors +
							":</b><ol>" +
							tmp;
						if (i > 0) {
							$("#opc_delivery_address_errors")
								.hide()
								.html(errors)
								.show();
						} else {
							$("#opc_delivery_address_errors")
								.hide()
								.html("");
						}
						// invoice address
						tmp = "";
						i = 0;
						for (var error in data.errors)
							if (
								error !== "indexOf" &&
								error.endsWith("_invoice")
							) {
								i = i + 1;
								tmp += "<li>" + data.errors[error] + "</li>";
							}
						tmp += "</ol>";
						var errors =
							"<b>" +
							txtThereis +
							" " +
							i +
							" " +
							txtErrors +
							":</b><ol>" +
							tmp;
						if (i > 0) {
							$("#opc_invoice_address_errors")
								.hide()
								.html(errors)
								.show();
						} else {
							$("#opc_invoice_address_errors")
								.hide()
								.html("");
						}
					} else {
						$("#opc_delivery_address_errors")
							.hide()
							.html("");
						$("#opc_invoice_address_errors")
							.hide()
							.html("");
						$("#customPayment").show();
					}
					updatePaymentMethodsDisplay(resolve); // todo this with promise
				})
				.fail((jqXHR, textStatus) => {
					this.handleError(jqXHR, textStatus);
					console.log(jqXHR);
					console.log(textStatus);
					reject();
				});
		});
	}

	updateCarrierForm(json) {
		var delivery = json.delivery;
		var carrier = json.carrier;
		var HOOK_CARRIER_FORM = json.HOOK_CARRIER_FORM;
		if (HOOK_CARRIER_FORM) {
			$("#HOOK_CARRIER_FORM").replaceWith(HOOK_CARRIER_FORM);
		}
		if (!carrier || !delivery) {
			return;
		}
	}

	updateCarrierSelection() {
		return new Promise(resolve => {
			updateCarrierSelectionAndGift(resolve);
		});
	}

	async updateWithCarrier(carrier) {
		if (kodminaCustomCheckout_isLpExpress(carrier.id_reference)) {
			var machineId = $(
				'.opc-main-block select[name="lp_terminal_machineid"]'
			).val();
			await this.updateLpExpressAddress(machineId);
			// await kodminaCustomCheckout_updateLpExpressAddress(machineId);
		} else if (kodminaCustomCheckout_isOmniva(carrier.id_reference)) {
			var terminalId = $(
				'.opc-main-block select[name="omnivalt_parcel_terminal"]'
			).val();
			await this.updateOmnivaAddress(terminalId);
			// await kodminaCustomCheckout_updateOmnivaAddress(terminalId);
		} else {
			await this.updateManualAddress();
			// await kodminaCustomCheckout_updateManualAddress();
		}
	}

	manageInvoiceCheckbox(checked) {
		if (checked) {
			if (
				$("#customCheckoutForm input[name=form_type]").val() === "full"
			) {
				var address1 = $(
					"#customCheckoutForm input[name=address1]"
				).val();
				var address1_invoice = $(
					"#invoiceAddressForm input[name=address1_invoice]"
				).val();
				if (!address1_invoice && address1) {
					$("#invoiceAddressForm input[name=address1_invoice]").val(
						address1
					);
				}
				var postcode = $(
					"#customCheckoutForm input[name=postcode]"
				).val();
				var postcode_invoice = $(
					"#invoiceAddressForm input[name=postcode_invoice]"
				).val();
				if (!postcode_invoice && postcode) {
					$("#invoiceAddressForm input[name=postcode_invoice]").val(
						postcode
					);
				}
				var city = $("#customCheckoutForm input[name=city]").val();
				var city_invoice = $(
					"#invoiceAddressForm input[name=city_invoice]"
				).val();
				if (!city_invoice && city) {
					$("#invoiceAddressForm input[name=city_invoice]").val(city);
				}
			}
			$("#invoiceAddressForm").show();
		} else {
			$("#invoiceAddressForm").hide();
		}
	}

	handleError(jqXHR, textStatus) {
		$("#customCheckoutOverlay .loading-spinner").css("display", "none");
		$("#customCheckoutError").css("display", "flex");
	}

	startCheckoutLoading() {
		if (this.lockLoading) {
			return;
		}
		$("#customCheckoutOverlay").css("display", "block");
	}

	stopCheckoutLoading() {
		if (this.lockLoading) {
			return;
		}
		setTimeout(() => {
			$("#customCheckoutOverlay").css("display", "none");
		}, 400);
	}

	_debounce(func, wait = 100) {
		let timeout;
		return function(...args) {
			clearTimeout(timeout);
			timeout = setTimeout(function() {
				func.apply(this, args);
			}, wait);
		};
	}
}

window["CustomCheckout"] = new CustomCheckout();
