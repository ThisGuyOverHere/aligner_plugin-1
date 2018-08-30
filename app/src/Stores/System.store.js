/*
 * Analyze Store
 */
import AppDispatcher from './AppDispatcher';
import EventEmitter from 'events';
import assign from 'object-assign';
import SystemConstants from "../Constants/System.constants";


EventEmitter.prototype.setMaxListeners(0);

let SystemStore = assign({}, EventEmitter.prototype, {

    statusLogin: false,

    emitChange: function (event, args) {
        this.emit.apply(this, arguments);
    },
});


// Register callback to handle all updates
AppDispatcher.register(function (action) {
    switch (action.actionType) {
        case SystemConstants.OPEN_LOGIN:
            SystemStore.statusLogin = action.status;
            SystemStore.emitChange(SystemConstants.OPEN_LOGIN,action.status);
            break;
    }
});


export default SystemStore;


