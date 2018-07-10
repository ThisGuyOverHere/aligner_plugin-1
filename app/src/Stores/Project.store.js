/*
 * Analyze Store
 */
import AppDispatcher from './AppDispatcher';
import EventEmitter from 'events';
import ProjectConstants from '../Constants/Project.constants';
import assign from 'object-assign';
import {List, Set, fromJS} from 'immutable';


EventEmitter.prototype.setMaxListeners(0);

let ProjectStore = assign({}, EventEmitter.prototype, {
    jobID: null,
    project: List(),

    updateAll: function (volumeAnalysis, project) {

    },
    emitChange: function (event, args) {
        this.emit.apply(this, arguments);
    },

    storeRows: function (rows) {
        const list = fromJS(rows);
        this.project = this.project.push(...list);
    },
    /**
     *
     * @param {Object[]} rows A List of rows to apply actions
     * @param {String} rows[].action The action to application on local row
     * @param {String} rows[].rif_order Depending on the received action takes different meanings.
     * if rows[].action = 'create' we refer to next order row.
     * if rows[].action = 'delete' we refer to row to delete.
     * if rows[].action = 'update' we refer to row to update.
     * @param {String} rows[].data The new row
     */
    storeRowsMovement: function (rows) {
        rows.map(row => {
            const index = this.project.findIndex(i => i.get('order') === row.rif_order);
            switch (row.action) {
                case 'delete':
                    this.project = this.project.delete(index);
                    break;
                case 'create':
                    this.project = this.project.insert(index, fromJS(row.data));
                    break;
                case 'update':
                    this.project = this.project.set(index, fromJS(row.data));
                    break;
            }
        });
    }

});


// Register callback to handle all updates
AppDispatcher.register(function (action) {
    switch (action.actionType) {
        case ProjectConstants.SET_JOB_ID:
            ProjectStore.jobID = action.jobID;
            break;
        case ProjectConstants.GET_ROWS:
            ProjectStore.storeRows(action.rows);
            ProjectStore.emitChange(ProjectConstants.RENDER_ROWS, ProjectStore.project.toJS());
            break;
        case ProjectConstants.CHANGE_SEGMENT_POSITION:
            ProjectStore.storeRowsMovement(action.rows);
            ProjectStore.emitChange(ProjectConstants.RENDER_ROWS, ProjectStore.project.toJS());
            break;


    }
});


export default ProjectStore;


