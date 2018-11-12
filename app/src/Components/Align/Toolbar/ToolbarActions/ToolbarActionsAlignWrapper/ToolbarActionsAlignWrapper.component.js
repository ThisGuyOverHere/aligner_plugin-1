import React, {Component} from 'react';
import PropTypes from "prop-types";
import ToolbarActionsAlignComponent from "./ToolbarActionsAlign/ToolbarActionsAlign.component";
import ToolbarActionsMergeAndAlignComponent from "./ToolbarActionsMergeAndAlign/ToolbarActionsMergeAndAlign.component";
import {Popup} from "semantic-ui-react";

class ToolbarActionsAlignWrapperComponent extends Component {

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
        let viewAlign = true;
        if (this.props.selection.count > 2
            && this.props.selection.source.count > 0
            && this.props.selection.target.count > 0) {
            viewAlign = false;
        }
        return (
            <span>{viewAlign ? <ToolbarActionsAlignComponent selection={this.props.selection}/> :
                <ToolbarActionsMergeAndAlignComponent selection={this.props.selection}/>}</span>
        );
    }

    onClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
    };
}

export default ToolbarActionsAlignWrapperComponent;