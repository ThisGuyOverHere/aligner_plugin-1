import React, {Component} from 'react';
import {ItemTypes} from '../../../Constants/Draggable.constants';
import {DropTarget} from 'react-dnd';
import ProjectActions from "../../../Actions/Project.actions";
import PropTypes from "prop-types";

const RowTarget = {
    canDrop(props, monitor) {
        const from = monitor.getItem(),
        types = {
            'source': 0,
            'target': 1
        };

        if(from.segment.order !== props.children[types[from.type]].props.segment.order){
            return true
        }
        return false
    },
    drop(props, monitor) {
        const from = monitor.getItem(),
            types = {
                'source': 0,
                'target': 1
            },
            log = {
                type: from.type,
                from: from.segment.order,
                to: props.children[types[from.type]].props.segment.order
            };
        ProjectActions.changeSegmentPosition(log);
    }
};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver(),
        canDrop: monitor.canDrop()
    }
}


class RowComponent extends Component {
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

    }

    render() {
        let rowClass = ['project-row'];

        const {connectDropTarget, isOver, canDrop} = this.props;
        if (isOver) {
            rowClass.push('dropHover');
        }
        return connectDropTarget(
            <div className={rowClass.join(' ')}>
                <div className="ui grid middle aligned">
                    <div className="one wide column center aligned">
                        {this.props.index}
                    </div>
                    <div className="fifteen wide column center aligned">
                        <div className="ui grid top aligned">
                            <div className="eight wide column">
                                {this.props.children[0]}
                            </div>
                            <div className="eight wide column">
                                {this.props.children[1]}
                            </div>
                        </div>
                    </div>

                </div>
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

RowComponent.propTypes = {
    index: PropTypes.number.isRequired,
    row: PropTypes.object.isRequired
};

export default DropTarget(ItemTypes.ITEM, RowTarget, collect)(RowComponent);