import React, {Component} from 'react';
import ProjectActions from "../../../../Actions/Project.actions";
import PropTypes from "prop-types";
import {getSegmentByOrder} from "../../../../Helpers/SegmentUtils.helper";
import ToolbarActionsMergeComponent from "./ToolbarActionsMerge/ToolbarActionsMerge.component";
import ToolbarActionsReverseComponent from "./ToolbarActionsReverse/ToolbarActionsReverse.component";
import ToolbarActionsSplitcomponent from "./ToolbarActionsSplit/ToolbarActionsSplitcomponent";

class ToolbarActionsComponent extends Component {

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
        return (
            <div className="segment-actions">
                <ul>
                    <li>
                        <ToolbarActionsReverseComponent selection={this.props.selection}/>
                    </li>
                    <li>
                        <ToolbarActionsMergeComponent selection={this.props.selection}/>

                    </li>
                    <li>
                        <ToolbarActionsSplitcomponent selection={this.props.selection}/>
                    </li>
                    <li>
                        <i
                            className={"icon pin"}
                        >
                        </i>
                    </li>
                    <li>
                        <i
                            className={"icon eye"}
                        >
                        </i>
                    </li>
                </ul>
            </div>
        );
    }
}

export default ToolbarActionsComponent;