import React, {Component} from 'react';
import {ItemTypes} from '../../../Constants/Draggable.constants';
import {DropTarget} from 'react-dnd';
import ProjectActions from "../../../Actions/Project.actions";
import PropTypes from "prop-types";
import SegmentComponent from "./Segment/Segment.component";
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants";
import ReactDOM, {findDOMNode} from "react-dom";

const RowTarget = {
    canDrop(props, monitor) {
        const from = monitor.getItem(),
            types = {
                'source': 0,
                'target': 1
            };

        if ((from.segment.order !== props.row[from.type].order)
            && (!props.mergeStatus || (props.mergeStatus && props.row[from.type].content_clean))) {
            return true
        }
        return false
    },
    drop(props, monitor, component) {
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

        if (!props.mergeStatus) {
            ProjectActions.changeSegmentPosition(log);

        } else {
            ProjectActions.mergeSegments(from.segment, props.row[from.type])
        }

        const rec = findDOMNode(component).getBoundingClientRect();
        const position = window.scrollY;
        //send type and order of inverse segment in drop position.
        setTimeout(() => {
            ProjectActions.animateChangeRowPosition(inverse[from.type], props.row[inverse[from.type]].order, position, rec);
        }, 0)

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
        scrollY: PropTypes.any,
        rec: PropTypes.any,
        row: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);

        this.state = {
            animated: false
        };
    }

    static getDerivedStateFromProps(props, state) {
        if (props.animate && !state.animated) {
            state.animated = true;
        }
        return state;
    }

    shouldComponentUpdate(nextProps, nextState) {

        return true;
    }

    getSnapshotBeforeUpdate(prevProps, prevState) {

        return null;
    }

    componentDidUpdate(prevProps, prevState, snapshot) {

    }

    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.ANIMATE_ROW_POSITION, this.animateRow);
        ProjectStore.addListener(ProjectConstants.SCROLL_TO_SEGMENT, this.scrollToThisSegment);

    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.ANIMATE_ROW_POSITION, this.animateRow);
        ProjectStore.removeListener(ProjectConstants.SCROLL_TO_SEGMENT, this.scrollToThisSegment);
    }

    render() {
        let rowClass = ['project-row'];
        const {connectDropTarget, isOver, canDrop, dragEl} = this.props;

        const dragElType = dragEl ? dragEl.type : undefined;
        if (isOver && dragElType && canDrop) {
            rowClass.push("dropHover");
        }
        if (this.state.animated) {
            rowClass.push("droppedRow");
        }

        return connectDropTarget(
            <div className={rowClass.join(' ')} ref={re => {
                this.ref = re
            }}>
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

    scrollToThisSegment = (data) => {
        if (data.ref === this.ref) {
            const domNode = ReactDOM.findDOMNode(this.ref);
            if (data.y) {
                window.scrollTo(0, data.y);
            } else {
                domNode.scrollIntoView({
                    block: 'center',
                    behavior: 'smooth'
                })
            }

        }
    };

    animateRow = (data) => {
        const {type, order, position, rec} = data;
        if (this.props.row[type].order === order) {
            clearInterval(this.interval);
            this.scrollAfterDrop(position, rec);
            this.interval = setInterval(() => {
                if (this.state.animated) {
                    this.setState({
                        animated: false
                    })
                }
            }, 2000);
            this.setState({
                animated: true
            })
        } else if (this.state.animated) {
            this.setState({
                animated: false
            })
        }
    };

    scrollAfterDrop = (position, rec) => {
        const top = findDOMNode(this.ref).getBoundingClientRect().top;
        if (rec.top > top) {
            window.scrollTo(0, position - (rec.top - top));
        }
    };

    interval = null;

}

export default DropTarget(ItemTypes.ITEM, RowTarget, collect)(RowComponent);