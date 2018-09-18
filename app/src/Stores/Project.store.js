/*
 * Analyze Store
 */
import AppDispatcher from './AppDispatcher';
import EventEmitter from 'events';
import ProjectConstants from '../Constants/Project.constants';
import assign from 'object-assign';
import {List, Set, fromJS} from 'immutable';
import env from "../Constants/Env.constants";
import {avgOrder, getSegmentByIndex} from "../Helpers/SegmentUtils.helper";


EventEmitter.prototype.setMaxListeners(0);

let ProjectStore = assign({}, EventEmitter.prototype, {
    jobID: null,
    job: {
        source: List(),
        target: List(),
        size: 0,
    },
    selection: {
        source: {
            count: 0,
            list: [],
            map: {}

        },
        target: {
            count: 0,
            list: [],
            map: {}
        },
        count: 0
    },
    mergeStatus: false,

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
        segments.source.map(item => {
            item.order = parseInt(item.order);
            item.next = parseInt(item.next);
            return item;
        });
        segments.target.map(item => {
            item.order = parseInt(item.order);
            item.next = parseInt(item.next);
            return item;
        });
        const source = fromJS(segments.source);
        const target = fromJS(segments.target);
        /*TODO: remove this when we remove select algorithm*/
        this.job.source = List();
        this.job.target = List();
        this.job.source = this.job.source.push(...source);
        this.job.target = this.job.target.push(...target);
    },
    /**
     *
     * @param {Object[]} changes A List of rows to apply actions
     * @param {String} changes[].action The action to application on local row
     * @param {String} changes[].rif_order Depending on the received action takes different meanings.
     * if changes[].action = 'create' we refer to next order row.
     * if changes[].action = 'delete' we refer to row to delete.
     * if changes[].action = 'update' we refer to row to update.
     * @param {String} changes[].data The new row
     * @param {String} changes[].type The type of segments (target or source)
     * @param {boolean} changes[].isEmptySegment use this for set the mock from order of index
     */
    storeMovements: function (changes) {
        const inverse = {
            source: 'target',
            target: 'source'
        };
        changes.map(change => {
            let index;
            let mock = Object.assign({}, env.segmentModel);
            if (change.rif_order) {
                index = this.job[change.type].findIndex(i => i.get('order') === change.rif_order);
            }
            switch (change.action) {
                case 'delete':
                    this.job[change.type] = this.job[change.type].setIn([+index - 1, 'next'], this.job[change.type].getIn([+index + 1, 'order']));
                    this.job[change.type] = this.job[change.type].delete(index);

                    //add empty space to the end for consistency of two list
                    this.storeMovements([{
                        type: change.type,
                        action: 'push'
                    }]);
                    break;
                case 'create':
                    if (change.isEmptySegment) {
                        change.data = Object.assign({}, env.segmentModel);
                        change.data.type = change.type;
                        change.data.order = avgOrder(this.job[change.type].getIn([+index - 1, 'order']), this.job[change.type].getIn([index, 'order']))
                    }
                    let createInverseSegment = this.job[inverse[change.type]].get(index).toJS();
                    //let's check if we are creating an empty line
                    if (!change.data.content_clean && !createInverseSegment.content_clean) {
                        //have a empty line, delete the inverse
                        this.storeMovements([{
                            type: inverse[change.type],
                            action: 'delete',
                            rif_order: createInverseSegment.order
                        }]);

                    } else {
                        //create the element
                        this.job[change.type] = this.job[change.type].setIn([+index - 1, 'next'], change.data.order);
                        change.data.next = this.job[change.type].getIn([index, 'order']);
                        this.job[change.type] = this.job[change.type].insert(index, fromJS(change.data));

                        //add empty space to the end for consistency of two list
                        this.storeMovements([{
                            type: inverse[change.type],
                            action: 'push'
                        }]);
                    }

                    break;
                case 'push':
                    //check if we have the last segment of job
                    let deleteInverseLastSegment = this.job[inverse[change.type]].last().toJS();
                    if (!deleteInverseLastSegment.next) {
                        //we have the last page
                        //check if is a empty segment
                        if (deleteInverseLastSegment.content_clean) {

                            //prepare mock for push
                            mock.order = +this.job[change.type].last().get('order') + env.orderElevation;
                            mock.type = change.type;
                            //edit previous last element segment next param.
                            this.job[change.type] = this.job[change.type].setIn([this.job[change.type].size-1,'next'], mock.order);
                            //add empty space at the end of list
                            this.job[change.type] = this.job[change.type].push(fromJS(mock));

                        } else {
                            //delete the last empty space inverse segment
                            this.job[inverse[change.type]] = this.job[inverse[change.type]].delete(this.job[inverse[change.type]].size - 1);
                            // change next of previous inverse segment
                            this.job[inverse[change.type]] = this.job[inverse[change.type]].setIn([this.job[inverse[change.type]].size-1,'next'], null);
                        }

                    } else {
                        //delete from local storage the last inverse element
                        this.job[inverse[change.type]] = this.job[inverse[change.type]].delete(this.job[inverse[change.type]].size - 1);
                    }
                    break;
                case 'update':
                    if (change.isEmptySegment) {
                        let createInverseSegment = this.job[inverse[change.type]].get(index).toJS();
                        //let's check if we are creating an empty line
                        if (!createInverseSegment.content_clean) {
                            //have a empty line, delete the inverse
                            this.storeMovements([{
                                type: inverse[change.type],
                                action: 'delete',
                                rif_order: createInverseSegment.order
                            }]);
                            this.storeMovements([{
                                type: change.type,
                                action: 'delete',
                                rif_order: change.rif_order
                            }]);
                        }else{
                            const el = this.job[change.type].get(index).toJS();
                            change.data = Object.assign({}, env.segmentModel);
                            change.data.type = change.type;
                            change.data.order = el.order;
                            change.data.next = el.next;
                            this.job[change.type] = this.job[change.type].set(index, fromJS(change.data));
                        }
                    }else{
                        this.job[change.type] = this.job[change.type].set(index, fromJS(change.data));
                    }
                    break;
            }
        });

        /*//Todo: remove this test
        const arrayS = this.job.source.toJS();
        console.log('#### SOURCE #####');
        for(let x= arrayS.length -5; x< arrayS.length; x++){
            console.log(arrayS[x].order+'       '+arrayS[x].next);
        }
        const arrayT = this.job.target.toJS();
        console.log('#### TARGET #####');
        for(let x= arrayT.length -5; x < arrayT.length; x++){
            console.log(arrayT[x].order+'       '+arrayT[x].next);
        }*/

        //Todo: remove this test
        const arrayS = this.job.source.toJS();
        console.log('#### SOURCE #####');
        for(let x= 0; x< arrayS.length; x++){
            console.log('['+x+']   '+arrayS[x].order+'       '+arrayS[x].next);
        }
        const arrayT = this.job.target.toJS();
        console.log('#### TARGET #####');
        for(let x= 0; x < arrayT.length; x++){
            console.log('['+x+']   '+arrayT[x].order+'       '+arrayT[x].next);
        }
    },

    addSegmentToSelection: function (order, type) {
        if (order > 0) {
            if (this.selection[type].map[order]) {
                this.selection[type].map[order] = false;
                this.selection[type].list.splice(this.selection[type].list.indexOf(order), 1);
            } else {
                this.selection[type].map[order] = true;
                this.selection[type].list.push(order);
            }
            this.selection.count = this.selection.source.list.length + this.selection.target.list.length;
            this.selection.source.count = this.selection.source.list.length;
            this.selection.target.count = this.selection.target.list.length;
        } else {
            this.selection = {
                source: {
                    count: 0,
                    list: [],
                    map: {}

                },
                target: {
                    count: 0,
                    list: [],
                    map: {}
                },
                count: 0
            };
        }
    }

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
            ProjectStore.storeMovements(action.changes, action.type);
            ProjectStore.emitChange(ProjectConstants.RENDER_ROWS, {
                source: ProjectStore.job.source.toJS(),
                target: ProjectStore.job.target.toJS()
            });
            break;
        case ProjectConstants.MERGE_STATUS:
            ProjectStore.mergeStatus = action.status;
            ProjectStore.emitChange(ProjectConstants.MERGE_STATUS, action.status);
            break;
        case ProjectConstants.ADD_SEGMENT_TO_SELECTION:
            ProjectStore.addSegmentToSelection(action.order, action.type);
            ProjectStore.emitChange(ProjectConstants.ADD_SEGMENT_TO_SELECTION, ProjectStore.selection);
            break;
        case ProjectConstants.SEGMENT_TO_SPLIT:
            ProjectStore.emitChange(ProjectConstants.SEGMENT_TO_SPLIT, action.segment);
            break;
        default:
            ProjectStore.emitChange(action.actionType, action.data);
    }
});


export default ProjectStore;


