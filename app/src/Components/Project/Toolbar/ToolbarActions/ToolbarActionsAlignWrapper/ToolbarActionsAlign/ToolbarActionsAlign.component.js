import React, {Component} from 'react';
import PropTypes from "prop-types";
import {Popup} from "semantic-ui-react";
import ProjectActions from "../../../../../../Actions/Project.actions";
import {getSegmentByIndex, getSegmentIndexByOrder} from "../../../../../../Helpers/SegmentUtils.helper";

class ToolbarActionsAlignComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {};
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        //check status of split action
        let disabled = true;
        let classes = ['icon', 'check'];
        if (this.props.selection.source.count === 1
            && this.props.selection.target.count === 1) {
            disabled = false;
        }
        const comp = <button
            disabled={disabled}
            onClick={this.onClick}>
            <i className={classes.join(" ")}></i>
            Align
        </button>;
        return (
            <Popup trigger={comp} content='shortcut alt+A' on='hover' inverted/>
        );
    }

    onClick = () => {
        const sourceIndex = getSegmentIndexByOrder(this.props.selection.source.list[0],'source');
        const targetToOrder = getSegmentByIndex(sourceIndex,'target').order;

        const log = {
            type: 'target',
            from: this.props.selection.target.list[0],
            to: targetToOrder
        };

        ProjectActions.requireChangeSegmentPosition(log);
        ProjectActions.addSegmentToSelection(-1);
    };

}

export default ToolbarActionsAlignComponent;