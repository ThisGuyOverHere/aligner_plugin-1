/*
 * Analyze Store
 */
import AppDispatcher from './AppDispatcher';
import EventEmitter from 'events';
import assign from 'object-assign';
import SystemConstants from "../Constants/System.constants";


EventEmitter.prototype.setMaxListeners(0);

let SystemStore = assign({}, EventEmitter.prototype, {

    emitChange: function (event, args) {
        this.emit.apply(this, arguments);
    },
});


// Register callback to handle all updates
AppDispatcher.register(function (action) {
    switch (action.actionType) {
        case SystemConstants.OPEN_LOGIN:
            SystemStore.emitChange(SystemConstants.OPEN_LOGIN,action.status);
            break;
        case SystemConstants.OPEN_EXPORT_MODAL:
            SystemStore.emitChange(SystemConstants.OPEN_EXPORT_MODAL,action.status);
            break;
        case SystemConstants.OPEN_RESET_PASSWORD_MODAL:
            SystemStore.emitChange(SystemConstants.OPEN_RESET_PASSWORD_MODAL,action.status);
            break;
        case SystemConstants.USER_STATUS:
            SystemStore.emitChange(SystemConstants.USER_STATUS,action.status);
            break;
    }
});


export default SystemStore;


