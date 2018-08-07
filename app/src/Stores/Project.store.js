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
    job: {
        source: List(),
        target: List()
    },

    updateAll: function (volumeAnalysis, project) {

    },
    emitChange: function (event, args) {
        this.emit.apply(this, arguments);
    },

    /**
     *
     * @param {Object} segments An object with source and target list
     * @param {Array} segments.source A list of source segments
     * @param {Array} segments.target A list of target segments
     */
    storeSegments: function (segments) {
        const source = fromJS(segments.source);
        const target = fromJS(segments.target);
        this.job.source = this.job.source.push(...source);
        this.job.target = this.job.target.push(...target);
    },
    /**
     *
     * @param {Object[]} movements A List of rows to apply actions
     * @param {String} movements[].action The action to application on local row
     * @param {String} movements[].rif_order Depending on the received action takes different meanings.
     * if rows[].action = 'create' we refer to next order row.
     * if rows[].action = 'delete' we refer to row to delete.
     * if rows[].action = 'update' we refer to row to update.
     * @param {String} movements[].data The new row
     * @param {String} type The type of segments (target or source)
     */
    storeMovements: function (movements, type) {
        rows.map(row => {
            const index = this.job[type].findIndex(i => i.get('order') === row.rif_order);
            switch (row.action) {
                case 'delete':
                    this.job[type] = this.job[type].delete(index);
                    break;
                case 'create':
                    this.job[type] = this.job[type].insert(index, fromJS(row.data));
                    break;
                case 'update':
                    this.job[type] = this.job[type].set(index, fromJS(row.data));
                    break;
            }
        });
    },

});


// Register callback to handle all updates
AppDispatcher.register(function (action) {
    switch (action.actionType) {
        case ProjectConstants.SET_JOB_ID:
            ProjectStore.jobID = action.jobID;
            break;
        case ProjectConstants.STORE_SEGMENTS:
            ProjectStore.storeSegments(action.segments);
            ProjectStore.emitChange(ProjectConstants.RENDER_ROWS, {
                source: ProjectStore.job.source.toJS(),
                target: ProjectStore.job.target.toJS()
            });
            break;
        case ProjectConstants.CHANGE_SEGMENT_POSITION:
            ProjectStore.storeMovements(action.movements,action.type);
            ProjectStore.emitChange(ProjectConstants.RENDER_ROWS, {
                source: ProjectStore.job.source.toJS(),
                target: ProjectStore.job.target.toJS()
            });
            break;
    }
});


export default ProjectStore;


