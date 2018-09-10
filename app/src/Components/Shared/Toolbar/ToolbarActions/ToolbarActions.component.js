import React, {Component} from 'react';
import ProjectActions from "../../../../Actions/Project.actions";

class ToolbarActionsComponent extends Component {

    constructor(props) {
        super(props);
        this.state = {
            merge: {
                active: false,
                disabled: false,
                classList: [],
            }
        };
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
                        <i
                            className={"icon object ungroup outline"}
                        >
                        </i>
                    </li>
                    <li>
                        <i
                            className={"icon random" +
                            this.state.merge.classList.join(' ') +
                            (this.state.merge.disabled ? ' disabled' : '')}
                            onClick={this.onMergeClick}>
                        </i>
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

    onMergeClick = () => {
        if (this.state.merge.active) {
            this.setState({
                merge: {
                    active: !this.state.merge.active,
                    disabled: this.state.merge.disabled,
                    classList: [''],
                }
            });
        } else {
            this.setState({
                merge: {
                    active: !this.state.merge.active,
                    disabled: this.state.merge.disabled,
                    classList: [' active'],
                }
            });
        }
    };


}

export default ToolbarActionsComponent;