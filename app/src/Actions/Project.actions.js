import ProjectStore from "../Stores/Project.store";
import ProjectConstants from '../Constants/Project.constants';
import {httpGetSegments, httpSplitSegment} from "../HttpRequests/Alignment.http";
import env from "../Constants/Env.constants";
import {
    avgOrder,
    getInverseSegmentByOrder,
    getSegmentByIndex,
    getSegmentByOrder,
    getSegmentIndexByOrder
} from "../Helpers/SegmentUtils.helper";
import {storeUndoOperations} from "../Helpers/SystemUtils.helper";

let AppDispatcher = require('../Stores/AppDispatcher');

let ProjectActions = {
        /**
         *
         * @param {Number} jobID The Job ID of current project
         */
        setJobID: function (jobID, jobPassword) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.SET_JOB_ID,
                jobID: jobID,
                jobPassword: jobPassword
            });
        },
        /**
         *
         * @param {Number} jobID The Job ID of current project
         * @param {String} jobPassword The password of current Job ID
         */
        getSegments: function (jobID, jobPassword) {
            httpGetSegments(jobID, jobPassword).then(response => {
                AppDispatcher.dispatch({
                    actionType: ProjectConstants.STORE_SEGMENTS,
                    segments: response.data
                })
            }, error => {
                AppDispatcher.dispatch({
                    actionType: ProjectConstants.JOB_ERROR,
                    error: error.response.data.errors[0].message
                });
                console.error(error.response.data.errors[0].message);
            });
        },


        /**
         *
         * @param {Object} log A log of move action from frontend
         * @param {String} log.type The type of segment: source or target
         * @param {Number} log.from The row's order of Drag action
         * @param {Number} log.to The row's order of Drop action
         */
        changeSegmentPosition: function (log) {


            let tmpJob = ProjectStore.job,
                changeData,
                changes = [],
                fromIndex = tmpJob[log.type].findIndex(i => i.get('order') === log.from),
                toIndex = tmpJob[log.type].findIndex(i => i.get('order') === log.to),
                mockFrom = Object.assign({}, env.segmentModel),
                mockToInverse = Object.assign({}, env.segmentModel);

            const inverse = {
                source: 'target',
                target: 'source'
            };

            /*
            * 1. creo un buco in corrispondenza della partenza
            * 2. sostituisco l'elemento in posizione di arrivo
            * 3. creo un elemento sotto la posizione di arrivo nella posizione opposta ed un buco nella stessa posizione
            *
            * */

            //1
            changes.push({
                type: log.type,
                action: 'update',
                isEmptySegment: true,
                rif_order: log.from
            });

            let segmentToPosition = tmpJob[log.type].get(toIndex).toJS();
            let segmentNextToPosition = tmpJob[log.type].get(toIndex + 1);
            segmentNextToPosition = segmentNextToPosition ? segmentNextToPosition.toJS() : null;
            let segmentfromPosition = tmpJob[log.type].get(fromIndex).toJS();
            let inverseSegmentToPosition = tmpJob[inverse[log.type]].get(toIndex + 1);
            inverseSegmentToPosition = inverseSegmentToPosition ? inverseSegmentToPosition.toJS() : null;
            let inverseSegmentToPositionBE = tmpJob[inverse[log.type]].get(toIndex).toJS().order;

            segmentfromPosition.order = segmentToPosition.order;
            segmentfromPosition.next = segmentToPosition.next;

            //2
            changes.push({
                type: log.type,
                action: 'update',
                rif_order: log.to,
                data: segmentfromPosition
            });


            if (segmentToPosition.content_clean) {
                //3
                segmentToPosition.order = segmentNextToPosition ? avgOrder(segmentToPosition.order, segmentToPosition.next) : null;
                segmentToPosition.next = segmentNextToPosition ? segmentNextToPosition.order : null;
                changes.push({
                    type: log.type,
                    action: segmentNextToPosition ? 'create' : 'push',
                    rif_order: segmentNextToPosition ? segmentNextToPosition.order : null,
                    data: segmentToPosition
                });

                changes.push({
                    type: inverse[log.type],
                    action: inverseSegmentToPosition ? 'create' : 'push',
                    rif_order: inverseSegmentToPosition ? inverseSegmentToPosition.order : null,
                    isEmptySegment: true
                });
            }


            AppDispatcher.dispatch({
                actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
                changes: changes,
                syncAPI: {
                    action: 'align',
                    data: {
                        jobID: ProjectStore.jobID,
                        jobPassword: ProjectStore.jobPassword,
                        order: log.from,
                        destination: log.to,
                        inverse_destination: inverseSegmentToPositionBE,
                        type: log.type
                    }
                }
            });
        },

        /**
         *
         * @param {Object} log A log of position and type of action
         * @param {Number} log.order The position where create a space
         * @param {String} log.type The type of segment: source or target
         */
        createSpaceSegment: function (log) {

            AppDispatcher.dispatch({
                actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
                changes: [{
                    type: log.type,
                    action: 'create',
                    rif_order: log.order,
                    isEmptySegment: true
                }]
            });

        },

        /**
         *
         * @param {Object} log A log of position and type of action
         * @param {Number} log.order The position where create a space
         * @param {String} log.type The type of segment: source or target
         */
        removeSpaceSegment: function (log) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
                changes: [{
                    type: log.type,
                    action: 'delete',
                    rif_order: log.order
                }]
            });

        },

        /**
         *
         * @param {Number} index of target segments for scroll
         */
        scrollToSegment: function (index) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.SCROLL_TO_SEGMENT,
                data: index
            });
        },
        /**
         *
         * @param {Object} selection a map with source and target lists
         * @param {Array} selection.source.list
         * @param {Number} selection.source.list[] the order of segment
         * @param {Array} selection.target.list
         * @param {Number} selection.target.list[] the order of segment
         */
        mergeAndAlignSegments: function (selection) {
            let matches = [];
            if (selection.source.count > 0) {
                selection.source.list.map((e) => {
                    matches.push({
                        type: 'source',
                        order: e
                    })
                });
            }
            if (selection.target.count > 0) {
                selection.target.list.map((e) => {
                    matches.push({
                        type: 'target',
                        order: e
                    })
                });
            }
            const sourceOrderList = selection.source.list.sort((a, b) => {
                return a - b
            });
            const firstSourceIndex = getSegmentIndexByOrder(sourceOrderList[0], 'source');
            const destination = getSegmentByIndex(firstSourceIndex, 'target').order;
            AppDispatcher.dispatch({
                actionType: ProjectConstants.MERGE_ALIGN,
                syncAPI: {
                    action: 'merge_align',
                    data: {
                        jobID: ProjectStore.jobID,
                        jobPassword: ProjectStore.jobPassword,
                        matches: matches,
                        destination: destination
                    }
                }
            });

        },

        //todo: move to utilis
        getLogsForMergeSegments: function (segments, type) {
            let changes = [];
            const inverse = {
                source: 'target',
                target: 'source'
            };

            let toMergeSegment = getSegmentByOrder(segments[0], type);

            for (let x = 1; x < segments.length; x++) {
                const segment = getSegmentByOrder(segments[x], type);

                const fromIndex = getSegmentIndexByOrder(segments[x], type);
                const fromInverse = getSegmentByIndex(fromIndex, inverse[type]);

                toMergeSegment.content_clean += " ";
                toMergeSegment.content_clean += segment.content_clean;
                toMergeSegment.content_raw += " ";
                toMergeSegment.content_raw += segment.content_raw;

                segment.content_clean = null;
                segment.content_raw = null;
                changes.push({
                    type: segment.type,
                    action: 'update',
                    rif_order: segment.order,
                    isEmptySegment: true
                });

            }
            changes.push({
                type: toMergeSegment.type,
                action: 'update',
                rif_order: toMergeSegment.order,
                data: toMergeSegment
            });

            return changes;
        },


        /**
         *
         * @param {Object} log A log of move action from frontend
         * @param {String} log.type The type of segment: source or target
         * @param {Number} log.from The row's order of Drag action
         * @param {Number} log.to The row's order of Drop action
         */
        getChangeSegmentPosition: function (log) { //todo: move to utilis


            let tmpJob = ProjectStore.job,
                changeData,
                changes = [],
                fromIndex = tmpJob[log.type].findIndex(i => i.get('order') === log.from),
                toIndex = tmpJob[log.type].findIndex(i => i.get('order') === log.to),
                mockFrom = Object.assign({}, env.segmentModel),
                mockToInverse = Object.assign({}, env.segmentModel);

            const inverse = {
                source: 'target',
                target: 'source'
            };

            /*
            * 1. creo un buco in corrispondenza della partenza
            * 2. sostituisco l'elemento in posizione di arrivo
            * 3. creo un elemento sotto la posizione di arrivo nella posizione opposta ed un buco nella stessa posizione
            *
            * */

            //1
            changes.push({
                type: log.type,
                action: 'update',
                isEmptySegment: true,
                rif_order: log.from
            });

            let segmentToPosition = tmpJob[log.type].get(toIndex).toJS();
            let segmentNextToPosition = tmpJob[log.type].get(toIndex + 1).toJS();
            let segmentfromPosition = tmpJob[log.type].get(fromIndex).toJS();
            let inverseSegmentToPosition = tmpJob[inverse[log.type]].get(toIndex + 1).toJS();

            segmentfromPosition.order = segmentToPosition.order;
            segmentfromPosition.next = segmentToPosition.next;

            //2
            changes.push({
                type: log.type,
                action: 'update',
                rif_order: log.to,
                data: segmentfromPosition
            });


            if (segmentToPosition.content_clean) {
                //3
                segmentToPosition.order = avgOrder(segmentToPosition.order, segmentToPosition.next);
                segmentToPosition.next = segmentNextToPosition.order;
                changes.push({
                    type: log.type,
                    action: 'create',
                    rif_order: segmentNextToPosition.order,
                    data: segmentToPosition
                });

                changes.push({
                    type: inverse[log.type],
                    action: 'create',
                    rif_order: inverseSegmentToPosition.order,
                    isEmptySegment: true
                });
            }

            return changes;
        },

        /**
         * @param {Number} jobID
         * @param {String} jobPassword
         * @param {Array} orders
         * @param {Object} orders[]
         * @param {number} orders[].order
         * @param {string} type
         */
        mergeSegments: function (jobID, jobPassword, orders, type) {
            const changes = this.getLogsForMergeSegments(orders, type);
            const inverseOrders = orders.map(order =>{
                return getInverseSegmentByOrder(order, type).order
            });
            console.log(orders,inverseOrders)
            AppDispatcher.dispatch({
                    actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
                    changes: changes,
                    syncAPI: {
                        action: 'merge',
                        data: {
                            jobID: ProjectStore.jobID,
                            jobPassword: ProjectStore.jobPassword,
                            order: orders,
                            inverses: inverseOrders,
                            type: type
                        }
                    }
                }
            );
        },

        /**
         *
         * @param {Object} segment1
         * @param {String} segment1.type
         * @param {String} segment1.content_clean
         * @param {String} segment1.content_raw
         * @param {Number} segment1.order
         * @param {Number} segment1.next
         * @param {Object} segment2
         * @param {String} segment2.content_clean
         * @param {String} segment2.content_raw
         * @param {Number} segment2.order
         * @param {Number} segment2.next
         * @param {String} segment2.type
         */
        reverseTwoSegments: function (segment1, segment2) {

            let tmpSegment1 = Object.assign({}, segment1);
            let tmpSegment2 = Object.assign({}, segment2);

            tmpSegment1.content_clean = segment2.content_clean;
            tmpSegment1.content_raw = segment2.content_raw;

            tmpSegment2.content_clean = segment1.content_clean;
            tmpSegment2.content_raw = segment1.content_raw;

            AppDispatcher.dispatch({
                actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
                changes: [
                    {
                        type: tmpSegment1.type,
                        action: 'update',
                        rif_order: tmpSegment1.order,
                        data: tmpSegment1
                    },
                    {
                        type: tmpSegment2.type,
                        action: 'update',
                        rif_order: tmpSegment2.order,
                        data: tmpSegment2
                    }],
                syncAPI: {
                    action: 'reverse',
                    data: {
                        jobID: ProjectStore.jobID,
                        jobPassword: ProjectStore.jobPassword,
                        order1: segment1.order,
                        order2: segment2.order,
                        type: segment2.type
                    }
                }
            });
        },

        /**
         *
         * @param type
         * @param order
         * @param position
         * @param rec
         */
        animateChangeRowPosition: function (type, order, position, rec) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.ANIMATE_ROW_POSITION,
                data: {
                    type: type,
                    order: order,
                    position: position,
                    rec: rec
                }
            });
        },
        /**
         *
         * @param {Number} order Send -1 for remove all selection
         * @param {String} type
         */
        addSegmentToSelection: function (order, type = null) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.ADD_SEGMENT_TO_SELECTION,
                order: order,
                type: type
            });
        },

        /**
         *
         * @param {Object} segment Segment to open, use false for close modal split
         */
        openSegmentToSplit: function (segment) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.SEGMENT_TO_SPLIT,
                segment: segment
            });
        },

        /**
         *
         * @param {Object} log A log of move action from frontend
         * @param {String} log.type The type of segment: source or target
         * @param {Number} log.from The row's order of Drag action
         * @param {Number} log.to The row's order of Drop action
         */
        requireChangeSegmentPosition: function (log) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.REQUIRE_SEGMENT_CHANGE_POSITION,
                data: log,
            });
        },

        /**
         * @param {Number} jobID
         * @param {String} jobPassword
         * @param {Object} data
         * @param {String} data.type
         * @param {Number} data.order
         * @param {Number} data.inverseOrder
         * @param {Array} data.positions
         */

        splitSegment: function (jobID, jobPassword, data) {

            httpSplitSegment(jobID, jobPassword, data).then(response => {
                if (!response.errors) {
                    storeUndoOperations(response.data.undo_actions_params)
                    AppDispatcher.dispatch({
                        actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
                        changes: response.data.operations
                    });
                } else {
                    response.errors.map(e => {
                        console.error(e.message);
                    })
                }

            }, error => {
                console.error(error)
            })
        },

        /**
         * on action hover in toolbar dispatch type of action
         * @param type
         */
        onActionHover: function (type) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.ON_ACTION_HOVER,
                type: type,
            });
        },


        /**
         *
         * @param {Array} changes A List of rows to apply actions
         * @param {Object} changes[]
         * @param {String} changes[].action The action to application on local row
         * @param {String} changes[].rif_order Depending on the received action takes different meanings.
         * if changes[].action = 'create' we refer to next order row.
         * if changes[].action = 'delete' we refer to row to delete.
         * if changes[].action = 'update' we refer to row to update.
         * if changes[].action = 'push' ignore rif_order.
         * @param {String} changes[].data The new row
         * @param {String} changes[].type The type of segments (target or source)
         * @param {boolean} changes[].isEmptySegment use this for set the mock from order of index
         */
        requireDirectChangesToStore: function (changes) {
            //todo: call backend for propagate;
            //console.log(changes);
            AppDispatcher.dispatch({
                actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
                changes: changes
            });
        },

        /**
         *
         * @param deletes
         * @param matches
         */
        deleteEmptyRows: function (deletes, matches) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.DELETE_ROWS,
                deletes: deletes,
                syncAPI: {
                    action: 'delete',
                    data: {
                        jobID: ProjectStore.jobID,
                        jobPassword: ProjectStore.jobPassword,
                        matches: matches
                    }
                }
            });
        },

        /**
         *
         * @param {boolean} status, true when user was on homepage, to initialise the store
         */
        emptyStore: function (status) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.EMPTY_STORE,
                status: status
            })
        },

        /**
         *
         * @param search
         */
        emitSearchResults: function (search) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.SEARCH_RESULTS,
                data: search
            })
        },

        /**
         *
         * @param hideSegmentsNavigatorData: hide segments data
         */
        emitHideNavigatorData: function (hideSegmentsNavigatorData) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.HIDE_SEGMENTS_NAVIGATOR,
                data: hideSegmentsNavigatorData
            })
        },

        /**
         *
         * @param misalignmentsData: misalignments segments data
         */
        emitMisalignmentsData: function (misalignmentsData) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.MISALIGNMENT_NAVIGATOR,
                data: misalignmentsData
            })
        },

        /**
         *
         * @param info , set latest job info api call result into STORE_JOB_INFO.
         */
        setJobInfo: function (info) {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.STORE_JOB_INFO,
                info: info
            })
        },

        getJobInfo: function () {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.GET_JOB_INFO
            })
        },

        /**
         * hide selected segments rows
         */
        hideSegments: function (matches) {
            AppDispatcher.dispatch({
                    actionType: ProjectConstants.HIDE_SEGMENTS,
                    changes: matches,
                    syncAPI: {
                        action: 'hide',
                        data: {
                            jobID: ProjectStore.jobID,
                            jobPassword: ProjectStore.jobPassword,
                            matches: matches
                        }
                    }
                }
            )
        },

        /**
         * show clicked row
         */
        showSegments: function (matches) {
            AppDispatcher.dispatch({
                    actionType: ProjectConstants.SHOW_SEGMENTS,
                    changes: matches,
                    syncAPI: {
                        action: 'show',
                        data: {
                            jobID: ProjectStore.jobID,
                            jobPassword: ProjectStore.jobPassword,
                            matches: matches
                        }
                    }
                }
            )
        },

    }
;


export default ProjectActions;
