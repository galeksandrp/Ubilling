<?php

if (cfr('GARAGE')) {
    $garage = new Garage();

    show_window('', $garage->renderControls());

    //creating new driver
    if (ubRouting::checkPost($garage::PROUTE_NEWDRIVER)) {
        $garage->createDriver(ubRouting::post($garage::PROUTE_NEWDRIVER));
        ubRouting::nav($garage::URL_ME . '&' . $garage::ROUTE_DRIVERS . '=true');
    }

    //creating new car
    if (ubRouting::checkPost(array($garage::PROUTE_NEWCAR, $garage::PROUTE_CARVENDOR, $garage::PROUTE_CARMODEL))) {
        $garage->createCar();
        ubRouting::nav($garage::URL_ME . '&' . $garage::ROUTE_CARS . '=true');
    }
    //deleting driver
    if (ubRouting::checkGet($garage::ROUTE_DRIVERDEL)) {
        $garage->deleteDriver(ubRouting::get($garage::ROUTE_DRIVERDEL));
        ubRouting::nav($garage::URL_ME . '&' . $garage::ROUTE_DRIVERS . '=true');
    }

    //editing driver
    if (ubRouting::checkPost($garage::PROUTE_DRIVEREDIT)) {
        $garage->setDriverCar(ubRouting::post($garage::PROUTE_DRIVEREDIT), ubRouting::post($garage::PROUTE_DRIVERCAR));
        ubRouting::nav($garage::URL_ME . '&' . $garage::ROUTE_DRIVERS . '=true');
    }

    //deleting car
    if (ubRouting::checkGet($garage::ROUTE_CARDEL)) {
        $carDeletionResult = $garage->deleteCar(ubRouting::get($garage::ROUTE_CARDEL));
        if (!$carDeletionResult) {
            ubRouting::nav($garage::URL_ME . '&' . $garage::ROUTE_CARS . '=true');
        } else {
            show_error($carDeletionResult);
            show_window('', wf_BackLink($garage::URL_ME . '&' . $garage::ROUTE_CARS . '=true'));
        }
    }

    //creating new mileage record
    if (ubRouting::checkPost(array($garage::PROUTE_MILEAGECAR, $garage::PROUTE_MILEAGEKM))) {
        $mileageCreateResult = $garage->createMileage(ubRouting::post($garage::PROUTE_MILEAGECAR), ubRouting::post($garage::PROUTE_MILEAGEKM), true);
        if (empty($mileageCreateResult)) {
            ubRouting::nav($garage::URL_ME . '&' . $garage::ROUTE_MILEAGE . '=true');
        } else {
            show_error($mileageCreateResult);
            show_window('', wf_BackLink($garage::URL_ME . '&' . $garage::ROUTE_MILEAGE . '=true'));
        }
    }


    //rendering cars list
    if (ubRouting::checkGet($garage::ROUTE_CARS)) {
        show_window(__('Available cars'), $garage->renderCarsList());
    }

    //rendering drivers list
    if (ubRouting::checkGet($garage::ROUTE_DRIVERS)) {
        show_window(__('Existing drivers'), $garage->renderDriversList());
    }

    //rendering mileage stats
    if (ubRouting::checkGet($garage::ROUTE_MILEAGE)) {
        show_window(__('Mileage'), 'TODO');
        deb($garage->renderMileageCreateForm());
    }
} else {
    show_error(__('Access denied'));
}