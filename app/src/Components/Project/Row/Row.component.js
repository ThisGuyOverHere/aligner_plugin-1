import React, {Component} from 'react';
import {ItemTypes} from '../../../Constants/Draggable.constants';
import {DropTarget} from 'react-dnd';
import ProjectActions from "../../../Actions/Project.actions";
import PropTypes from "prop-types";
import SegmentComponent from "./Segment/Segment.component";

const RowTarget = {
    canDrop(props, monitor) {
        const from = monitor.getItem(),
            types = {
                'source': 0,
                'target': 1
            };

        if ((from.segment.order !== props.row[from.type].order)
            && (!props.mergeStatus || (props.mergeStatus && props.row[from.type].clean))) {
            return true
        }
        return false
    },
    drop(props, monitor) {
        const from = monitor.getItem(),
            inverse = {
                'source': 'target',
                'target': 'target'
            },
            log = {
                type: from.type,
                from: from.segment.order,
                to: props.row[from.type].order
            };
        ProjectActions.changeSegmentPosition(log);

        //send type and order of inverse segment in drop position.
        props.setAnimatedRow(inverse[from.type], props.row[inverse[from.type]].order);
    }
};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver(),
        canDrop: monitor.canDrop(),
        dragEl: monitor.getItem()
    }
}

class RowComponent extends Component {

    static propTypes = {
        index: PropTypes.number.isRequired,
        animate: PropTypes.bool,
        mergeStatus: PropTypes.bool.isRequired,
        setAnimatedRow: PropTypes.func,
        row: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);

        this.state = {};
    }

    static getDerivedStateFromProps(props, state) {

        return null;
    }

    shouldComponentUpdate(nextProps, nextState) {

        return true;
    }

    getSnapshotBeforeUpdate(prevProps, prevState) {

        return null;
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        if (this.props.animate) {
            setTimeout(() => {
                this.props.setAnimatedRow(null, null)
            }, 800)
        }
    }

    render() {
        let rowClass = ['project-row'];
        const {connectDropTarget, isOver, canDrop, dragEl, animate} = this.props;

        const dragElType = dragEl ? dragEl.type : undefined;
        if (isOver && dragElType && canDrop) {
            rowClass.push("dropHover");
        }
        if (animate) {
            rowClass.push("droppedRow");
        }

        return connectDropTarget(
            <div className={rowClass.join(' ')}>
                <div>{this.props.index}</div>
                <SegmentComponent type="source"
                                  mergeStatus={canDrop && isOver && this.props.mergeStatus && dragEl.type === 'source'}
                                  segment={this.props.row.source}/>
                <SegmentComponent type="target"
                                  mergeStatus={canDrop && isOver && this.props.mergeStatus && dragEl.type === 'target'}
                                  segment={this.props.row.target}/>
            </div>
        );
    }

    componentDidCatch() {

    }

    componentDidMount() {

    }

    componentWillUnmount() {
    }
}

export default DropTarget(ItemTypes.ITEM, RowTarget, collect)(RowComponent);