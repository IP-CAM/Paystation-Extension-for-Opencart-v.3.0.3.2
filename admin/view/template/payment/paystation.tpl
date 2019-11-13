<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-paystation" data-toggle="tooltip" title="<?php echo $button_save; ?>"
                        class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>"
                   class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-paystation"
                      class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-account"><?php echo $entry_account; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="paystation_account" value="<?php echo $paystation_account; ?>"
                                   placeholder="<?php echo $entry_account; ?>" id="input-account" class="form-control"/>
                            <?php if ($error_account) { ?>
                            <div class="text-danger"><?php echo $error_account; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-gateway"><?php echo $entry_gateway; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="paystation_gateway" value="<?php echo $paystation_gateway; ?>"
                                   placeholder="<?php echo $entry_gateway; ?>" id="input-gateway" class="form-control"/>
                            <?php if ($error_gateway) { ?>
                            <div class="text-danger"><?php echo $error_gateway; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-hmac"><?php echo $entry_hmac; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="paystation_hmac" value="<?php echo $paystation_hmac; ?>"
                                   placeholder="<?php echo $entry_hmac; ?>" id="input-hmac" class="form-control"/>
                            <?php if ($error_hmac) { ?>
                            <div class="text-danger"><?php echo $error_hmac; ?></div>
                            <?php } ?>

                        </div>

                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-title">
                            <?php echo $entry_title; ?></label>
                        <div class="col-sm-10">
                            <?php if (!isset($paystation_title) || empty($paystation_title)) $paystation_title = 'Pay using your credit card. ';
                            ?>
                            <input type="text" name="paystation_title" value="<?php echo $paystation_title; ?>"
                                   placeholder="<?php echo $entry_title; ?>" id="input-title" class="form-control"/>
                            <?php if (isset($error_title) && !empty ($error_title)) { ?>
                            <div class="text-danger"><?php echo $error_title; ?></div>

                            <?php } ?>
                            <p>This text will display next to the payment method in the checkout.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_test; ?></label>
                        <div class="col-sm-10">
                            <label class="radio-inline">
                                <?php 
                                if ($paystation_test===NULL)  $paystation_test = true;
                                if ($paystation_test) { ?>
                                <input type="radio" name="paystation_test" value="1" checked="checked"/>
                                <?php echo $text_yes; ?>
                                <?php } else { ?>
                                <input type="radio" name="paystation_test" value="1"/>
                                <?php echo $text_yes; ?>
                                <?php } ?>
                            </label>
                            <label class="radio-inline">
                                <?php if (!$paystation_test) { ?>
                                <input type="radio" name="paystation_test" value="0" checked="checked"/>
                                <?php echo $text_no; ?>
                                <?php } else { ?>
                                <input type="radio" name="paystation_test" value="0"/>
                                <?php echo $text_no; ?>
                                <?php } ?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_postback ?></label>
                        <div class="col-sm-10">
                            <label class="radio-inline">
                                <?php 
                                if ($paystation_postback===NULL)  $paystation_postback = true;
                                if ($paystation_postback) { ?>
                                <input type="radio" name="paystation_postback" value="1" checked="checked"/>
                                <?php echo $text_yes; ?>
                                <?php } else { ?>
                                <input type="radio" name="paystation_postback" value="1"/>
                                <?php echo $text_yes; ?>
                                <?php } ?>
                            </label>
                            <label class="radio-inline">
                                <?php if (!$paystation_postback) { ?>
                                <input type="radio" name="paystation_postback" value="0" checked="checked"/>
                                <?php echo $text_no; ?>
                                <?php } else { ?>
                                <input type="radio" name="paystation_postback" value="0"/>
                                <?php echo $text_no; ?>
                                <?php } ?>
                            </label>
                            <p>We strongly suggest setting 'Enable Postback' to 'Yes' as it will
                                allow the cart to capture payment results even if your customers
                                re-direct is interrupted. However, if your development/test environment
                                is local or on a network that cannot receive connections from
                                the internet, you must set 'Enable Postback' to 'No'.</p>
                            <p>Your Paystation account needs to reflect your Opencart settings
                                accurately, otherwise order status will not update correctly.
                                Email support@paystation.co.nz with your Paystation ID and advise
                                whether 'Enable Postback' is set to 'Yes' or 'No' in your
                                Opencart settings. </p>

                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-order-status"><?php echo $entry_order_status; ?></label>
                        <div class="col-sm-10">
                            <select name="paystation_order_status_id" id="input-order-status" class="form-control">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if ($order_status['order_status_id'] == $paystation_order_status_id) { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"
                                        selected="selected"><?php echo $order_status['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
                        <div class="col-sm-10">
                            <select name="paystation_geo_zone_id" id="input-geo-zone" class="form-control">
                                <option value="0"><?php echo $text_all_zones; ?></option>
                                <?php foreach ($geo_zones as $geo_zone) { ?>
                                <?php if ($geo_zone['geo_zone_id'] == $paystation_geo_zone_id) { ?>
                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"
                                        selected="selected"><?php echo $geo_zone['name']; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                        <div class="col-sm-10">
                            <select name="paystation_status" id="input-status" class="form-control">
                                <?php if ($paystation_status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-sort-order"><?php echo $entry_sort_order; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="paystation_sort_order"
                                   value="<?php echo $paystation_sort_order; ?>"
                                   placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order"
                                   class="form-control"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>
