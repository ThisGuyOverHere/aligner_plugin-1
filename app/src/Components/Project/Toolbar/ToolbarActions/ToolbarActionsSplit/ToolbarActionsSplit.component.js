import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import {getSegmentByOrder} from "../../../../../Helpers/SegmentUtils.helper";
import {Popup} from "semantic-ui-react";

class ToolbarActionsSplitComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            type: 'split'
        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        //check status of split action
        let disabled = false;
        let splitClasses = ['icon', 'arrows', 'alternate', 'horizontal'];
        if (this.props.selection.count > 1) {
            disabled = true;
        }
        return (<button
                disabled={disabled}
                onMouseOver={this.onHover}
                onMouseOut={this.onMouseLeave}
                onClick={this.onSplitClick}>
                <i className={splitClasses.join(" ")}></i>
                split
            </button>
        );
    }

    onSplitClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
        ProjectActions.openSegmentToSplit(getSegmentByOrder(this.props.selection[type].list[0], type));
        ProjectActions.addSegmentToSelection(-1);
        ProjectActions.onActionHover(null);
    };

    onHover = () => {
        ProjectActions.onActionHover(this.state.type);
    };

    onMouseLeave = () => {
        ProjectActions.onActionHover(null);
    };

}

export default ToolbarActionsSplitComponent;